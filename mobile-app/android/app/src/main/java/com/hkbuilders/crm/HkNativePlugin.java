package com.hkbuilders.crm;

import android.Manifest;
import android.content.Context;
import android.content.Intent;
import android.database.Cursor;
import android.location.LocationManager;
import android.net.Uri;
import android.os.Build;
import android.os.PowerManager;
import android.provider.CallLog;
import android.provider.Settings;

import androidx.core.content.ContextCompat;

import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.PermissionState;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.Permission;
import com.getcapacitor.annotation.PermissionCallback;

/**
 * What the CRM page can ask the phone for (window.Capacitor plugin "HkNative"):
 * permission status and requests, background location tracking, call log.
 */
@CapacitorPlugin(
    name = "HkNative",
    permissions = {
        @Permission(alias = "location", strings = { Manifest.permission.ACCESS_FINE_LOCATION, Manifest.permission.ACCESS_COARSE_LOCATION }),
        @Permission(alias = "background", strings = { Manifest.permission.ACCESS_BACKGROUND_LOCATION }),
        @Permission(alias = "callLog", strings = { Manifest.permission.READ_CALL_LOG }),
        @Permission(alias = "notifications", strings = { Manifest.permission.POST_NOTIFICATIONS })
    }
)
public class HkNativePlugin extends Plugin {

    private boolean granted(String alias) {
        // These permissions do not exist on older Android versions
        if (alias.equals("background") && Build.VERSION.SDK_INT < 29) return granted("location");
        if (alias.equals("notifications") && Build.VERSION.SDK_INT < 33) return true;
        return getPermissionState(alias) == PermissionState.GRANTED;
    }

    private JSObject status() {
        Context ctx = getContext();
        LocationManager lm = (LocationManager) ctx.getSystemService(Context.LOCATION_SERVICE);
        boolean gps = lm != null && (lm.isProviderEnabled(LocationManager.GPS_PROVIDER)
                || lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER));
        PowerManager pm = (PowerManager) ctx.getSystemService(Context.POWER_SERVICE);
        JSObject r = new JSObject();
        r.put("location", granted("location"));
        r.put("background", granted("background"));
        r.put("callLog", granted("callLog"));
        r.put("notifications", granted("notifications"));
        r.put("gpsOn", gps);
        r.put("batteryUnrestricted", pm == null || pm.isIgnoringBatteryOptimizations(ctx.getPackageName()));
        r.put("tracking", LocationService.isRunning(ctx));
        r.put("android", Build.VERSION.SDK_INT);
        return r;
    }

    @PluginMethod
    public void getStatus(PluginCall call) { call.resolve(status()); }

    @PluginMethod
    public void requestLocation(PluginCall call) {
        if (granted("location")) { call.resolve(status()); return; }
        requestPermissionForAlias("location", call, "afterRequest");
    }

    // Android 11+ opens the app's location settings page: the agent picks "Allow all the time"
    @PluginMethod
    public void requestBackground(PluginCall call) {
        if (granted("background") || !granted("location")) { call.resolve(status()); return; }
        requestPermissionForAlias("background", call, "afterRequest");
    }

    @PluginMethod
    public void requestCallLog(PluginCall call) {
        if (granted("callLog")) { call.resolve(status()); return; }
        requestPermissionForAlias("callLog", call, "afterRequest");
    }

    @PluginMethod
    public void requestNotifications(PluginCall call) {
        if (granted("notifications")) { call.resolve(status()); return; }
        requestPermissionForAlias("notifications", call, "afterRequest");
    }

    @PermissionCallback
    private void afterRequest(PluginCall call) { call.resolve(status()); }

    @PluginMethod
    public void openAppSettings(PluginCall call) {
        Intent i = new Intent(Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                Uri.parse("package:" + getContext().getPackageName()));
        i.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        getContext().startActivity(i);
        call.resolve();
    }

    @PluginMethod
    public void openLocationSettings(PluginCall call) {
        Intent i = new Intent(Settings.ACTION_LOCATION_SOURCE_SETTINGS);
        i.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
        getContext().startActivity(i);
        call.resolve();
    }

    // Asks Android not to stop the app to save battery (some phones kill it otherwise)
    @PluginMethod
    public void requestBatteryUnrestricted(PluginCall call) {
        try {
            Intent i = new Intent(Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS,
                    Uri.parse("package:" + getContext().getPackageName()));
            i.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            getContext().startActivity(i);
        } catch (Exception e) {
            Intent i = new Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS);
            i.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK);
            getContext().startActivity(i);
        }
        call.resolve();
    }

    // url: the CRM's /agent/location address; login: its /login address (remember-me re-login)
    @PluginMethod
    public void startTracking(PluginCall call) {
        String url = call.getString("url");
        String login = call.getString("login");
        if (url == null || !url.startsWith("https://")) { call.reject("Bad url"); return; }
        if (!granted("location")) { call.reject("Location not allowed"); return; }
        Intent i = new Intent(getContext(), LocationService.class)
                .putExtra("url", url).putExtra("login", login);
        ContextCompat.startForegroundService(getContext(), i);
        call.resolve(status());
    }

    @PluginMethod
    public void stopTracking(PluginCall call) {
        getContext().getSharedPreferences(LocationService.PREFS, Context.MODE_PRIVATE)
                .edit().putBoolean("active", false).remove("queue").apply();
        getContext().stopService(new Intent(getContext(), LocationService.class));
        call.resolve();
    }

    // Calls since `since` (milliseconds), oldest first, at most 300
    @PluginMethod
    public void getCallLog(PluginCall call) {
        if (!granted("callLog")) { call.reject("Call log not allowed"); return; }
        long since = call.getLong("since", System.currentTimeMillis() - 2L * 24 * 3600 * 1000);
        JSArray list = new JSArray();
        String[] cols = { CallLog.Calls._ID, CallLog.Calls.NUMBER, CallLog.Calls.TYPE, CallLog.Calls.DATE, CallLog.Calls.DURATION };
        try (Cursor c = getContext().getContentResolver().query(CallLog.Calls.CONTENT_URI, cols,
                CallLog.Calls.DATE + " > ?", new String[] { String.valueOf(since) }, CallLog.Calls.DATE + " ASC")) {
            int n = 0;
            while (c != null && c.moveToNext() && n++ < 300) {
                JSObject o = new JSObject();
                o.put("id", c.getString(0));
                o.put("number", c.getString(1));
                o.put("type", c.getInt(2));
                o.put("date", c.getLong(3));
                o.put("duration", c.getLong(4));
                list.put(o);
            }
        } catch (Exception e) {
            call.reject("Could not read call log");
            return;
        }
        JSObject r = new JSObject();
        r.put("calls", list);
        call.resolve(r);
    }
}
