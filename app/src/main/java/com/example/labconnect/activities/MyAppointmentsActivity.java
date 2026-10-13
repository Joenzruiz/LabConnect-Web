package com.example.labconnect.activities;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.adapters.AppointmentAdapter;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.Appointment;
import java.util.ArrayList;
import java.util.List;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MyAppointmentsActivity extends AppCompatActivity {

    private RecyclerView rvAppointments;
    private Toolbar toolbar;
    private android.widget.TextView tvNoAppointments;
    private com.google.android.material.tabs.TabLayout tabLayout;
    private List<Appointment> allAppointments = new ArrayList<>();
    private int selectedTabPosition = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_my_appointments);

        toolbar = findViewById(R.id.toolbar);
        rvAppointments = findViewById(R.id.rvAppointments);
        tvNoAppointments = findViewById(R.id.tvNoAppointments);
        tabLayout = findViewById(R.id.tabLayout);

        // Custom header handlers
        android.widget.ImageButton btnBack = findViewById(R.id.btnBack);
        if (btnBack != null) btnBack.setOnClickListener(v -> finish());
        
        android.widget.TextView btnBook = findViewById(R.id.btnBook);
        if (btnBook != null) {
            btnBook.setOnClickListener(v -> {
                startActivity(new android.content.Intent(this, BookAppointmentActivity.class));
            });
        }

        // Setup bottom navigation
        com.google.android.material.bottomnavigation.BottomNavigationView bottomNavigation = findViewById(R.id.bottomNavigation);
        if (bottomNavigation != null) {
            bottomNavigation.setSelectedItemId(R.id.nav_appointments);
            bottomNavigation.setOnItemSelectedListener(item -> {
                int id = item.getItemId();
                if (id == R.id.nav_home) {
                    startActivity(new android.content.Intent(this, HomeActivity.class));
                    return true;
                }
                if (id == R.id.nav_appointments) return true;
                if (id == R.id.nav_results) {
                    startActivity(new android.content.Intent(this, LabResultsActivity.class));
                    return true;
                }
                if (id == R.id.nav_profile) {
                    startActivity(new android.content.Intent(this, ProfileActivity.class));
                    return true;
                }
                return false;
            });
        }
        rvAppointments.setLayoutManager(new LinearLayoutManager(this));

        tabLayout.addOnTabSelectedListener(new com.google.android.material.tabs.TabLayout.OnTabSelectedListener() {
            @Override
            public void onTabSelected(com.google.android.material.tabs.TabLayout.Tab tab) {
                selectedTabPosition = tab.getPosition();
                filterAndDisplayAppointments();
            }
            @Override public void onTabUnselected(com.google.android.material.tabs.TabLayout.Tab tab) {}
            @Override public void onTabReselected(com.google.android.material.tabs.TabLayout.Tab tab) {}
        });

        // Load appointments happens in onResume now
    }

    private final android.os.Handler realTimeHandler = new android.os.Handler(android.os.Looper.getMainLooper());
    private final Runnable realTimeRunnable = new Runnable() {
        @Override
        public void run() {
            loadAppointments();
            realTimeHandler.postDelayed(this, 3000); // Check every 3 seconds for status change
        }
    };

    @Override
    protected void onResume() {
        super.onResume();
        loadAppointments();
        realTimeHandler.removeCallbacks(realTimeRunnable);
        realTimeHandler.postDelayed(realTimeRunnable, 3000);
    }

    @Override
    protected void onPause() {
        super.onPause();
        realTimeHandler.removeCallbacks(realTimeRunnable);
    }

    private void loadAppointments() {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);

        if (userId == 0) {
            tvNoAppointments.setVisibility(View.VISIBLE);
            tvNoAppointments.setText("Please log in again.");
            return;
        }

        ApiClient.getApiService().getAppointments(userId).enqueue(new Callback<List<Appointment>>() {
            @Override
            public void onResponse(Call<List<Appointment>> call, Response<List<Appointment>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    allAppointments = response.body();
                    filterAndDisplayAppointments();
                } else {
                    tvNoAppointments.setVisibility(View.VISIBLE);
                    tvNoAppointments.setText("No appointments found.");
                    rvAppointments.setVisibility(View.GONE);
                }
            }

            @Override
            public void onFailure(Call<List<Appointment>> call, Throwable t) {
                Toast.makeText(MyAppointmentsActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
                tvNoAppointments.setVisibility(View.VISIBLE);
                tvNoAppointments.setText("Could not load appointments.");
                rvAppointments.setVisibility(View.GONE);
            }
        });
    }

    private void filterAndDisplayAppointments() {
        List<Appointment> filteredList = new ArrayList<>();
        for (Appointment appt : allAppointments) {
            String status = appt.getStatus();
            boolean isUpcoming = "Pending".equalsIgnoreCase(status) || "Confirmed".equalsIgnoreCase(status);
            boolean isHistory = "Completed".equalsIgnoreCase(status) || "Cancelled".equalsIgnoreCase(status);

            if (selectedTabPosition == 0 && isUpcoming) {
                filteredList.add(appt);
            } else if (selectedTabPosition == 1 && isHistory) {
                filteredList.add(appt);
            }
        }

        if (filteredList.isEmpty()) {
            rvAppointments.setVisibility(View.GONE);
            tvNoAppointments.setVisibility(View.VISIBLE);
            tvNoAppointments.setText(selectedTabPosition == 0 ? "No upcoming appointments" : "No appointment history");
        } else {
            rvAppointments.setVisibility(View.VISIBLE);
            tvNoAppointments.setVisibility(View.GONE);
            AppointmentAdapter adapter = new AppointmentAdapter(filteredList, (appointment, position) -> {
                // Show a detailed confirmation before cancelling
                String message = "Test: " + appointment.getTestName()
                        + "\nDoctor: " + appointment.getDoctorName()
                        + "\nDate: " + appointment.getDate()
                        + "\nTime: " + appointment.getTime()
                        + "\n\nAre you sure you want to cancel this appointment?";

                new androidx.appcompat.app.AlertDialog.Builder(this)
                        .setTitle("⚠ Cancel Appointment")
                        .setMessage(message)
                        .setPositiveButton("Yes, Cancel it", (dialog, which) -> cancelAppointment(appointment))
                        .setNegativeButton("No, Keep it", null)
                        .setCancelable(false)
                        .show();
            });
            rvAppointments.setAdapter(adapter);
        }
    }

    private void cancelAppointment(Appointment appointment) {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);

        java.util.Map<String, Integer> body = new java.util.HashMap<>();
        body.put("appointment_id", appointment.getId());
        body.put("patient_id", userId);

        ApiClient.getApiService().cancelAppointment(body).enqueue(new Callback<com.example.labconnect.models.ApiResponse>() {
            @Override
            public void onResponse(Call<com.example.labconnect.models.ApiResponse> call, Response<com.example.labconnect.models.ApiResponse> response) {
                if (response.isSuccessful()) {
                    // Instantly remove from the list so the user sees it disappear
                    allAppointments.remove(appointment);
                    filterAndDisplayAppointments();
                    Toast.makeText(MyAppointmentsActivity.this,
                        "Appointment cancelled successfully.", Toast.LENGTH_SHORT).show();
                    // Then reload fresh data from server in background
                    loadAppointments();
                } else {
                    Toast.makeText(MyAppointmentsActivity.this,
                        "Could not cancel. Please try again.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<com.example.labconnect.models.ApiResponse> call, Throwable t) {
                Toast.makeText(MyAppointmentsActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
