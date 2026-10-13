package com.example.labconnect;

import android.app.Application;
import androidx.appcompat.app.AppCompatDelegate;

public class LabConnectApp extends Application {
    @Override
    public void onCreate() {
        super.onCreate();
        // Enforce Light theme globally across all devices so all cards,
        // text fields, dialogs, and labels remain crisp and 100% visible
        AppCompatDelegate.setDefaultNightMode(AppCompatDelegate.MODE_NIGHT_NO);
    }
}
