package com.example.labconnect.activities;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.adapters.NotificationAdapter;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.ApiResponse;
import com.example.labconnect.models.AppNotification;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class NotificationsActivity extends AppCompatActivity {

    private RecyclerView rv;
    private View tvNoNotifications;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_notifications);

        android.widget.ImageButton btnBack = findViewById(R.id.btnBack);
        if (btnBack != null) btnBack.setOnClickListener(v -> finish());

        rv = findViewById(R.id.rvNotifications);
        rv.setLayoutManager(new LinearLayoutManager(this));
        tvNoNotifications = findViewById(R.id.tvNoNotifications);
    }

    private final android.os.Handler realTimeHandler = new android.os.Handler(android.os.Looper.getMainLooper());
    private final Runnable realTimeRunnable = new Runnable() {
        @Override
        public void run() {
            loadNotifications();
            realTimeHandler.postDelayed(this, 3000); // Check every 3 seconds for new notifications
        }
    };

    @Override
    protected void onResume() {
        super.onResume();
        loadNotifications();
        realTimeHandler.removeCallbacks(realTimeRunnable);
        realTimeHandler.postDelayed(realTimeRunnable, 3000);
    }

    @Override
    protected void onPause() {
        super.onPause();
        realTimeHandler.removeCallbacks(realTimeRunnable);
    }

    private void loadNotifications() {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);

        if (userId == 0) {
            showEmpty();
            return;
        }

        // Mark all as read in the background when the screen opens
        Map<String, Integer> body = new HashMap<>();
        body.put("patient_id", userId);
        ApiClient.getApiService().markNotificationsRead(body).enqueue(new Callback<ApiResponse>() {
            @Override public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {}
            @Override public void onFailure(Call<ApiResponse> call, Throwable t) {}
        });

        // Fetch all notifications
        ApiClient.getApiService().getNotifications(userId).enqueue(new Callback<List<AppNotification>>() {
            @Override
            public void onResponse(Call<List<AppNotification>> call, Response<List<AppNotification>> response) {
                if (response.isSuccessful() && response.body() != null && !response.body().isEmpty()) {
                    List<AppNotification> notifications = response.body();
                    rv.setVisibility(View.VISIBLE);
                    tvNoNotifications.setVisibility(View.GONE);
                    rv.setAdapter(new NotificationAdapter(notifications));
                } else {
                    showEmpty();
                }
            }

            @Override
            public void onFailure(Call<List<AppNotification>> call, Throwable t) {
                showEmpty();
            }
        });
    }

    private void showEmpty() {
        rv.setVisibility(View.GONE);
        tvNoNotifications.setVisibility(View.VISIBLE);
    }
}
