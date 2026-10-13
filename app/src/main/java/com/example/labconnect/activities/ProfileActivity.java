package com.example.labconnect.activities;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Button;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import com.example.labconnect.R;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.User;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ProfileActivity extends AppCompatActivity {

    private android.view.View btnLogout;
    private Toolbar toolbar;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_profile);

        toolbar = findViewById(R.id.toolbar);
        btnLogout = findViewById(R.id.btnLogout);

        // Setup Bottom Navigation
        com.google.android.material.bottomnavigation.BottomNavigationView bottomNavigation = findViewById(R.id.bottomNavigation);
        if (bottomNavigation != null) {
            bottomNavigation.setSelectedItemId(R.id.nav_profile);
            bottomNavigation.setOnItemSelectedListener(item -> {
                int id = item.getItemId();
                if (id == R.id.nav_home) {
                    startActivity(new Intent(this, HomeActivity.class));
                    return true;
                }
                if (id == R.id.nav_appointments) {
                    startActivity(new Intent(this, MyAppointmentsActivity.class));
                    return true;
                }
                if (id == R.id.nav_results) {
                    startActivity(new Intent(this, LabResultsActivity.class));
                    return true;
                }
                if (id == R.id.nav_profile) return true;
                return false;
            });
        }

        findViewById(R.id.btnEditProfile).setOnClickListener(v -> startActivity(new Intent(ProfileActivity.this, EditProfileActivity.class)));
        findViewById(R.id.btnResultPin).setOnClickListener(v -> {
            Intent intent = new Intent(ProfileActivity.this, ResultPinActivity.class);
            intent.putExtra("isChangeMode", true);
            startActivity(intent);
        });
        findViewById(R.id.btnChangePassword).setOnClickListener(v ->
                startActivity(new Intent(ProfileActivity.this, ChangePasswordActivity.class)));
        findViewById(R.id.btnNotificationsMenu).setOnClickListener(v ->
                startActivity(new Intent(ProfileActivity.this, NotificationsActivity.class)));

        btnLogout.setOnClickListener(v -> {
            getSharedPreferences("UserPrefs", MODE_PRIVATE).edit().clear().apply();
            Intent intent = new Intent(ProfileActivity.this, LoginActivity.class);
            intent.setFlags(Intent.FLAG_ACTIVITY_NEW_TASK | Intent.FLAG_ACTIVITY_CLEAR_TASK);
            startActivity(intent);
            finish();
        });
    }

    @Override
    protected void onResume() {
        super.onResume();
        loadUserProfile();
        loadUnreadNotificationCount();
    }

    private void loadUnreadNotificationCount() {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);
        if (userId == 0) return;

        ApiClient.getApiService().getUnreadNotificationCount(userId).enqueue(new Callback<com.example.labconnect.models.UnreadCountResponse>() {
            @Override
            public void onResponse(Call<com.example.labconnect.models.UnreadCountResponse> call, Response<com.example.labconnect.models.UnreadCountResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    int count = response.body().getUnreadCount();
                    TextView tvNotifBadge = findViewById(R.id.tvNotifBadge);
                    if (count > 0) {
                        tvNotifBadge.setVisibility(android.view.View.VISIBLE);
                        tvNotifBadge.setText(String.valueOf(count));
                    } else {
                        tvNotifBadge.setVisibility(android.view.View.GONE);
                    }
                }
            }

            @Override
            public void onFailure(Call<com.example.labconnect.models.UnreadCountResponse> call, Throwable t) {
                // Ignore failure
            }
        });
    }

    private void loadUserProfile() {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);

        if (userId == 0) {
            // Fallback to locally stored data
            displayProfile(
                prefs.getString("name", "User"),
                prefs.getString("email", ""),
                prefs.getString("mobile", ""),
                prefs.getString("dob", ""),
                prefs.getString("gender", ""),
                prefs.getString("address", "")
            );
            return;
        }

        ApiClient.getApiService().getProfile(userId).enqueue(new Callback<User>() {
            @Override
            public void onResponse(Call<User> call, Response<User> response) {
                if (response.isSuccessful() && response.body() != null) {
                    User user = response.body();
                    // Also update local cache
                    prefs.edit()
                        .putString("name", user.getFullName())
                        .putString("email", user.getEmail())
                        .putString("mobile", user.getMobile())
                        .putString("dob", user.getDob())
                        .putString("gender", user.getGender())
                        .putString("address", user.getAddress())
                        .apply();
                    displayProfile(user.getFullName(), user.getEmail(), user.getMobile(), user.getDob(), user.getGender(), user.getAddress());
                } else {
                    // Fallback to local
                    displayProfile(
                        prefs.getString("name", "User"),
                        prefs.getString("email", ""),
                        prefs.getString("mobile", ""),
                        prefs.getString("dob", ""),
                        prefs.getString("gender", ""),
                        prefs.getString("address", "")
                    );
                }
            }

            @Override
            public void onFailure(Call<User> call, Throwable t) {
                // Fallback to local on network failure
                displayProfile(
                    prefs.getString("name", "User"),
                    prefs.getString("email", ""),
                    prefs.getString("mobile", ""),
                    prefs.getString("dob", ""),
                    prefs.getString("gender", ""),
                    prefs.getString("address", "")
                );
            }
        });
    }

    private void displayProfile(String name, String email, String mobile, String dob, String gender, String address) {
        ((TextView) findViewById(R.id.tvProfileName)).setText(name);
        ((TextView) findViewById(R.id.tvProfileEmail)).setText(email);
        ((TextView) findViewById(R.id.tvGenderValue)).setText(gender);
        ((TextView) findViewById(R.id.tvDobValue)).setText(dob);
        ((TextView) findViewById(R.id.tvMobileValue)).setText(mobile);
        ((TextView) findViewById(R.id.tvAddressValue)).setText(address);

        if (name != null && name.trim().length() > 0) {
            String[] split = name.trim().split("\\s+");
            String initials = "";
            if (split.length > 0 && split[0].length() > 0) initials += split[0].substring(0, 1).toUpperCase();
            if (split.length > 1 && split[1].length() > 0) initials += split[1].substring(0, 1).toUpperCase();
            if (initials.isEmpty()) initials = "U";
            ((TextView) findViewById(R.id.tvProfileInitials)).setText(initials);
        }
    }
}
