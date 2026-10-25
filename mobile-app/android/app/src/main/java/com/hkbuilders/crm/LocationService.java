package com.hkbuilders.crm;

import android.annotation.SuppressLint;
import android.app.Notification;
import android.app.NotificationChannel;
import android.app.NotificationManager;
import android.app.PendingIntent;
import android.app.Service;
import android.content.Context;
import android.content.Intent;
import android.content.SharedPreferences;
import android.content.pm.ServiceInfo;
import android.location.Location;
import android.location.LocationListener;
import android.location.LocationManager;
import android.os.BatteryManager;
import android.os.Build;
import android.os.Bundle;
import android.os.Handler;
import android.os.IBinder;
import android.os.Looper;
import android.webkit.CookieManager;

import androidx.annotation.NonNull;
import androidx.core.app.NotificationCompat;

import org.json.JSONArray;
import org.json.JSONObject;

import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.nio.charset.StandardCharsets;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

/**
 * Sends the agent's location to the CRM while they are logged in, also when
 * the app is in the background or the screen is off. Android requires the
 * permanent notification this service shows.
 *
 * Same rules as the website: a point every 3 minutes, sooner after moving
 * 150 m, never more than once a minute. Points that cannot be sent (no
 * internet) are kept and sent later. When the CRM answers "not logged in",
 * the service tries the remember-me login once, then stops.
 */
public class LocationService extends Service implements LocationListener {

    static final String PREFS = "hk_tracking";
    private static final String CHANNEL = "hk_location";
    private static final int NOTIF_ID = 4101;
    private static final long EVERY_MS = 3 * 60 * 1000L;
    private static final long MIN_MS = 60 * 1000L;
    private static final float MOVE_M = 150f;
    private static final int MAX_QUEUE = 500;

    private LocationManager lm;
    private final Handler main = new Handler(Looper.getMainLooper());
    private final ExecutorService net = Executors.newSingleThreadExecutor();
    private Location lastSent;
    private long lastSentAt = 0;
    private Location lastFix;

    // Standing still gives no new fixes, so send the last fix every 3 minutes
    private final Runnable heartbeat = new Runnable() {
        @Override public void run() {
            if (lastFix != null && System.currentTimeMillis() - lastFix.getTime() < 10 * 60 * 1000L) {
                consider(lastFix, true);
            }
            main.postDelayed(this, EVERY_MS);
        }
    };

    static boolean isRunning(Context c) {
        return c.getSharedPreferences(PREFS, MODE_PRIVATE).getBoolean("active", false);
    }

    @Override public IBinder onBind(Intent intent) { return null; }

    @SuppressLint("MissingPermission")
    @Override
    public int onStartCommand(Intent intent, int flags, int startId) {
        SharedPreferences p = getSharedPreferences(PREFS, MODE_PRIVATE);
        if (intent != null && intent.hasExtra("url")) {
            p.edit().putString("url", intent.getStringExtra("url"))
                    .putString("login", intent.getStringExtra("login"))
                    .putBoolean("active", true).apply();
        }
        if (!p.getBoolean("active", false) || p.getString("url", null) == null) {
            stopSelf();
            return START_NOT_STICKY;
        }

        startForegroundNow();

        if (lm == null) {
            lm = (LocationManager) getSystemService(LOCATION_SERVICE);
            try {
                if (lm.isProviderEnabled(LocationManager.GPS_PROVIDER)) {
                    lm.requestLocationUpdates(LocationManager.GPS_PROVIDER, MIN_MS, 0f, this, Looper.getMainLooper());
                }
                if (lm.isProviderEnabled(LocationManager.NETWORK_PROVIDER)) {
                    lm.requestLocationUpdates(LocationManager.NETWORK_PROVIDER, MIN_MS, 0f, this, Looper.getMainLooper());
                }
                Location known = lm.getLastKnownLocation(LocationManager.GPS_PROVIDER);
                if (known == null) known = lm.getLastKnownLocation(LocationManager.NETWORK_PROVIDER);
                if (known != null) lastFix = known;
            } catch (SecurityException e) {
                // Permission was taken away: nothing to send, the CRM page shows the notice
                stopTracking();
                return START_NOT_STICKY;
            }
            main.postDelayed(heartbeat, 5000);
        }
        return START_STICKY;
    }

    private void startForegroundNow() {
        NotificationManager nm = (NotificationManager) getSystemService(NOTIFICATION_SERVICE);
        if (Build.VERSION.SDK_INT >= 26 && nm.getNotificationChannel(CHANNEL) == null) {
            NotificationChannel ch = new NotificationChannel(CHANNEL, "Location sharing", NotificationManager.IMPORTANCE_LOW);
            ch.setDescription("Shown while HK CRM shares your location with admin");
            nm.createNotificationChannel(ch);
        }
        Intent open = new Intent(this, MainActivity.class).setFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP);
        PendingIntent pi = PendingIntent.getActivity(this, 0, open,
                PendingIntent.FLAG_UPDATE_CURRENT | PendingIntent.FLAG_IMMUTABLE);
        Notification n = new NotificationCompat.Builder(this, CHANNEL)
                .setSmallIcon(android.R.drawable.ic_menu_mylocation)
                .setContentTitle("HK CRM")
                .setContentText("Sharing your location with admin while you are logged in")
                .setContentIntent(pi)
                .setOngoing(true)
                .setPriority(NotificationCompat.PRIORITY_LOW)
                .build();
        if (Build.VERSION.SDK_INT >= 29) {
            startForeground(NOTIF_ID, n, ServiceInfo.FOREGROUND_SERVICE_TYPE_LOCATION);
        } else {
            startForeground(NOTIF_ID, n);
        }
    }

    @Override public void onLocationChanged(@NonNull Location loc) {
        lastFix = loc;
        consider(loc, false);
    }
    @Override public void onLocationChanged(@NonNull List<Location> locations) {
        if (!locations.isEmpty()) onLocationChanged(locations.get(locations.size() - 1));
    }
    @Override public void onProviderEnabled(@NonNull String provider) {}
    @Override public void onProviderDisabled(@NonNull String provider) {}
    @Override public void onStatusChanged(String provider, int status, Bundle extras) {}

    private void consider(Location loc, boolean force) {
        long now = System.currentTimeMillis();
        if (now - lastSentAt < MIN_MS) return;
        if (!force && lastSent != null && now - lastSentAt < EVERY_MS && lastSent.distanceTo(loc) < MOVE_M) return;
        lastSent = loc;
        lastSentAt = now;
        enqueue(loc);
        // The cookie store must be read on the main thread
        final String cookies = CookieManager.getInstance().getCookie(prefs().getString("url", ""));
        net.execute(() -> flush(cookies, true));
    }

    private SharedPreferences prefs() { return getSharedPreferences(PREFS, MODE_PRIVATE); }

    private synchronized void enqueue(Location loc) {
        try {
            JSONArray q = new JSONArray(prefs().getString("queue", "[]"));
            JSONObject pt = new JSONObject();
            pt.put("id", UUID.randomUUID().toString());
            pt.put("lat", loc.getLatitude());
            pt.put("lng", loc.getLongitude());
            pt.put("accuracy", Math.round(loc.getAccuracy()));
            pt.put("time", new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ssXXX", Locale.US).format(new Date()));
            int battery = batteryPct();
            if (battery >= 0) pt.put("battery", battery);
            q.put(pt);
            while (q.length() > MAX_QUEUE) q.remove(0);
            prefs().edit().putString("queue", q.toString()).apply();
        } catch (Exception ignored) { }
    }

    private int batteryPct() {
        BatteryManager bm = (BatteryManager) getSystemService(BATTERY_SERVICE);
        return bm == null ? -1 : bm.getIntProperty(BatteryManager.BATTERY_PROPERTY_CAPACITY);
    }

    /** Runs on the network thread. */
    private void flush(String cookies, boolean mayRelogin) {
        String url = prefs().getString("url", null);
        if (url == null) return;
        JSONArray q;
        synchronized (this) {
            try { q = new JSONArray(prefs().getString("queue", "[]")); } catch (Exception e) { q = new JSONArray(); }
        }
        if (q.length() == 0) return;
        try {
            HttpURLConnection c = (HttpURLConnection) new URL(url).openConnection();
            c.setInstanceFollowRedirects(false);
            c.setConnectTimeout(15000);
            c.setReadTimeout(20000);
            c.setRequestMethod("POST");
            c.setDoOutput(true);
            c.setRequestProperty("Content-Type", "application/json");
            c.setRequestProperty("X-Requested-With", "HKCRMApp");
            if (cookies != null) c.setRequestProperty("Cookie", cookies);
            byte[] body = new JSONObject().put("points", q).toString().getBytes(StandardCharsets.UTF_8);
            try (OutputStream os = c.getOutputStream()) { os.write(body); }
            int code = c.getResponseCode();
            c.disconnect();

            if (code == 200) {
                synchronized (this) {
                    // Drop what was sent; keep points added while sending
                    JSONArray now = new JSONArray(prefs().getString("queue", "[]"));
                    JSONArray rest = new JSONArray();
                    for (int i = q.length(); i < now.length(); i++) rest.put(now.get(i));
                    prefs().edit().putString("queue", rest.toString()).apply();
                }
            } else if (code == 302 || code == 401 || code == 403) {
                // Not logged in any more: try the remember-me cookie once
                if (mayRelogin && relogin(cookies)) {
                    main.post(() -> {
                        String fresh = CookieManager.getInstance().getCookie(url);
                        net.execute(() -> flush(fresh, false));
                    });
                } else {
                    main.post(this::stopTracking);
                }
            }
            // Other answers (server busy, no internet): keep the queue for next time
        } catch (Exception ignored) { }
    }

    /** Opens the login page with the remember-me cookie; stores the new session cookies. */
    private boolean relogin(String cookies) {
        String login = prefs().getString("login", null);
        if (login == null || cookies == null || !cookies.contains("crm_remember")) return false;
        try {
            HttpURLConnection c = (HttpURLConnection) new URL(login).openConnection();
            c.setInstanceFollowRedirects(false);
            c.setConnectTimeout(15000);
            c.setReadTimeout(20000);
            c.setRequestProperty("Cookie", cookies);
            int code = c.getResponseCode();
            String to = c.getHeaderField("Location");
            Map<String, List<String>> headers = c.getHeaderFields();
            c.disconnect();
            if (code != 302 || to == null || to.contains("/login")) return false;
            List<String> set = headers.get("Set-Cookie");
            if (set == null || set.isEmpty()) return false;
            final List<String> toSet = set;
            final Object lock = new Object();
            final boolean[] done = {false};
            main.post(() -> {
                CookieManager cm = CookieManager.getInstance();
                for (String sc : toSet) cm.setCookie(login, sc);
                cm.flush();
                synchronized (lock) { done[0] = true; lock.notifyAll(); }
            });
            synchronized (lock) { if (!done[0]) lock.wait(5000); }
            return done[0];
        } catch (Exception e) {
            return false;
        }
    }

    void stopTracking() {
        prefs().edit().putBoolean("active", false).remove("queue").apply();
        stopSelf();
    }

    @Override
    public void onDestroy() {
        main.removeCallbacks(heartbeat);
        if (lm != null) {
            try { lm.removeUpdates(this); } catch (Exception ignored) { }
        }
        net.shutdown();
        super.onDestroy();
    }
}
