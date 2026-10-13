package com.example.labconnect.activities;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.MenuItem;
import android.view.View;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.appcompat.app.AppCompatActivity;
import androidx.cardview.widget.CardView;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.adapters.AnnouncementAdapter;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.Announcement;
import com.example.labconnect.models.Appointment;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import com.google.android.material.navigation.NavigationBarView;
import java.util.ArrayList;
import java.util.List;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class HomeActivity extends AppCompatActivity {

    private TextView tvGreeting;
    private View btnNotifications;
    private TextView btnManageAppt;
    private CardView cardLatestResult, btnBookTest, btnAllResults;
    private View layoutUpcomingAppt, layoutLatestResult;
    private RecyclerView rvAnnouncements;
    private BottomNavigationView bottomNavigation;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_home);

        tvGreeting = findViewById(R.id.tvGreeting);
        btnNotifications = findViewById(R.id.btnNotifications);
        btnManageAppt = findViewById(R.id.btnManageAppt);
        cardLatestResult = findViewById(R.id.cardLatestResult);
        btnBookTest = findViewById(R.id.btnBookTest);
        btnAllResults = findViewById(R.id.btnAllResults);
        rvAnnouncements = findViewById(R.id.rvAnnouncements);
        bottomNavigation = findViewById(R.id.bottomNavigation);
        layoutUpcomingAppt = findViewById(R.id.layoutUpcomingAppt);
        layoutLatestResult = findViewById(R.id.layoutLatestResult);

        rvAnnouncements.setLayoutManager(new LinearLayoutManager(this));

        btnBookTest.setOnClickListener(v -> startActivity(new Intent(HomeActivity.this, BookAppointmentActivity.class)));
        btnAllResults.setOnClickListener(v -> startActivity(new Intent(HomeActivity.this, LabResultsActivity.class)));
        cardLatestResult.setOnClickListener(v -> startActivity(new Intent(HomeActivity.this, LabResultsActivity.class)));
        btnManageAppt.setOnClickListener(v -> startActivity(new Intent(HomeActivity.this, MyAppointmentsActivity.class)));
        btnNotifications.setOnClickListener(v -> startActivity(new Intent(HomeActivity.this, NotificationsActivity.class)));

        bottomNavigation.setOnItemSelectedListener(new NavigationBarView.OnItemSelectedListener() {
            @Override
            public boolean onNavigationItemSelected(@NonNull MenuItem item) {
                int id = item.getItemId();
                if (id == R.id.nav_home) return true;
                if (id == R.id.nav_appointments) {
                    startActivity(new Intent(HomeActivity.this, MyAppointmentsActivity.class));
                    return true;
                }
                if (id == R.id.nav_results) {
                    startActivity(new Intent(HomeActivity.this, LabResultsActivity.class));
                    return true;
                }
                if (id == R.id.nav_profile) {
                    startActivity(new Intent(HomeActivity.this, ProfileActivity.class));
                    return true;
                }
                return false;
            }
        });
    }

    private final android.os.Handler realTimeHandler = new android.os.Handler(android.os.Looper.getMainLooper());
    private final Runnable realTimeRunnable = new Runnable() {
        @Override
        public void run() {
            loadLatestAppointment();
            loadUnreadNotificationCount();
            realTimeHandler.postDelayed(this, 3000); // Check every 3 seconds for real-time status update
        }
    };

    @Override
    protected void onResume() {
        super.onResume();
        loadUserData();
        loadAnnouncements();
        loadLatestAppointment();
        loadUnreadNotificationCount();
        realTimeHandler.removeCallbacks(realTimeRunnable);
        realTimeHandler.postDelayed(realTimeRunnable, 3000);
    }

    @Override
    protected void onPause() {
        super.onPause();
        realTimeHandler.removeCallbacks(realTimeRunnable);
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
                    TextView tvNotifBadge = findViewById(R.id.tvHomeNotifBadge);
                    if (count > 0) {
                        tvNotifBadge.setVisibility(View.VISIBLE);
                        tvNotifBadge.setText(String.valueOf(count));
                    } else {
                        tvNotifBadge.setVisibility(View.GONE);
                    }
                }
            }

            @Override
            public void onFailure(Call<com.example.labconnect.models.UnreadCountResponse> call, Throwable t) {
                // Ignore failure
            }
        });
    }

    private void loadUserData() {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        String name = prefs.getString("name", "User");

        // Extract first name only
        String firstName = name.trim().split("\\s+")[0];

        // Time-based greeting
        int hour = java.util.Calendar.getInstance().get(java.util.Calendar.HOUR_OF_DAY);
        String timeGreeting;
        if (hour < 12) timeGreeting = "Good morning,";
        else if (hour < 18) timeGreeting = "Good afternoon,";
        else timeGreeting = "Good evening,";

        tvGreeting.setText(timeGreeting);
        TextView tvGreetingName = findViewById(R.id.tvGreetingName);
        if (tvGreetingName != null) tvGreetingName.setText(firstName);
    }

    private void loadAnnouncements() {
        ApiClient.getApiService().getAnnouncements().enqueue(new Callback<List<Announcement>>() {
            @Override
            public void onResponse(Call<List<Announcement>> call, Response<List<Announcement>> response) {
                if (response.isSuccessful() && response.body() != null && !response.body().isEmpty()) {
                    rvAnnouncements.setVisibility(View.VISIBLE);
                    findViewById(R.id.tvNoAnnouncements).setVisibility(View.GONE);
                    rvAnnouncements.setAdapter(new AnnouncementAdapter(response.body()));
                } else {
                    rvAnnouncements.setVisibility(View.GONE);
                    findViewById(R.id.tvNoAnnouncements).setVisibility(View.VISIBLE);
                }
            }

            @Override
            public void onFailure(Call<List<Announcement>> call, Throwable t) {
                rvAnnouncements.setVisibility(View.GONE);
                findViewById(R.id.tvNoAnnouncements).setVisibility(View.VISIBLE);
            }
        });
    }

    private void loadLatestAppointment() {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);
        if (userId == 0) {
            layoutUpcomingAppt.setVisibility(View.GONE);
            layoutLatestResult.setVisibility(View.GONE);
            return;
        }

        ApiClient.getApiService().getAppointments(userId).enqueue(new Callback<List<Appointment>>() {
            @Override
            public void onResponse(Call<List<Appointment>> call, Response<List<Appointment>> response) {
                if (response.isSuccessful() && response.body() != null && !response.body().isEmpty()) {
                    // Find the most recently booked upcoming appointment
                    Appointment latest = null;
                    for (Appointment a : response.body()) {
                        String s = a.getStatus();
                        if ("Pending".equalsIgnoreCase(s) || "Confirmed".equalsIgnoreCase(s)) {
                            latest = a; // list is already sorted newest-first
                            break;
                        }
                    }

                    if (latest != null) {
                        layoutUpcomingAppt.setVisibility(View.VISIBLE);

                        TextView tvApptTime = findViewById(R.id.tvApptTime);
                        TextView tvApptName = findViewById(R.id.tvApptName);
                        TextView tvApptStatus = findViewById(R.id.tvApptStatus);
                        TextView tvApptDoctor = findViewById(R.id.tvApptDoctor);

                        tvApptTime.setText(latest.getDate() + " • " + latest.getTime());
                        tvApptName.setText(latest.getTestName());
                        tvApptDoctor.setText(latest.getDoctorName());

                        String status = latest.getStatus();
                        tvApptStatus.setText(status);
                        if ("Confirmed".equalsIgnoreCase(status)) {
                            tvApptStatus.setBackgroundTintList(android.content.res.ColorStateList.valueOf(android.graphics.Color.parseColor("#B2DFDB")));
                            tvApptStatus.setTextColor(android.graphics.Color.parseColor("#004D40"));
                        } else {
                            // Pending
                            tvApptStatus.setBackgroundTintList(android.content.res.ColorStateList.valueOf(android.graphics.Color.parseColor("#FFE082")));
                            tvApptStatus.setTextColor(android.graphics.Color.parseColor("#E65100"));
                        }
                    } else {
                        layoutUpcomingAppt.setVisibility(View.GONE);
                    }
                    layoutLatestResult.setVisibility(View.GONE);
                } else {
                    layoutUpcomingAppt.setVisibility(View.GONE);
                    layoutLatestResult.setVisibility(View.GONE);
                }
            }

            @Override
            public void onFailure(Call<List<Appointment>> call, Throwable t) {
                layoutUpcomingAppt.setVisibility(View.GONE);
                layoutLatestResult.setVisibility(View.GONE);
            }
        });
    }
}
