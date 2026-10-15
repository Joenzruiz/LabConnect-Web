package com.example.labconnect.activities;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Toast;
import androidx.annotation.Nullable;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.SearchView;
import androidx.appcompat.widget.Toolbar;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.adapters.ResultAdapter;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.LabResult;
import java.util.ArrayList;
import java.util.List;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class LabResultsActivity extends AppCompatActivity {

    private RecyclerView rvResults;
    private Toolbar toolbar;
    private SearchView searchView;
    private static final int PIN_REQUEST_CODE = 1001;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_lab_results);



        toolbar = findViewById(R.id.toolbar);
        rvResults = findViewById(R.id.rvResults);
        searchView = findViewById(R.id.searchView);

        // Bottom Navigation
        com.google.android.material.bottomnavigation.BottomNavigationView bottomNavigation = findViewById(R.id.bottomNavigation);
        if (bottomNavigation != null) {
            bottomNavigation.setSelectedItemId(R.id.nav_results);
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
                if (id == R.id.nav_results) return true;
                if (id == R.id.nav_profile) {
                    startActivity(new Intent(this, ProfileActivity.class));
                    return true;
                }
                return false;
            });
        }
        rvResults.setLayoutManager(new LinearLayoutManager(this));

        // Launch PIN verification before showing results
        Intent intent = new Intent(this, ResultPinActivity.class);
        startActivityForResult(intent, PIN_REQUEST_CODE);
    }

    @Override
    protected void onActivityResult(int requestCode, int resultCode, @Nullable Intent data) {
        super.onActivityResult(requestCode, resultCode, data);
        if (requestCode == PIN_REQUEST_CODE) {
            if (resultCode == RESULT_OK) {
                loadResults();
            } else {
                finish();
            }
        }
    }

    private void loadResults() {
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);

        if (userId == 0) {
            showEmpty();
            return;
        }

        ApiClient.getApiService().getResults(userId).enqueue(new Callback<List<LabResult>>() {
            @Override
            public void onResponse(Call<List<LabResult>> call, Response<List<LabResult>> response) {
                if (response.isSuccessful() && response.body() != null && !response.body().isEmpty()) {
                    List<LabResult> results = response.body();
                    ResultAdapter adapter = new ResultAdapter(results, result -> {
                        Intent intent = new Intent(LabResultsActivity.this, ResultDetailsActivity.class);
                        intent.putExtra("testName", result.getTestName());
                        intent.putExtra("date", result.getDate());
                        intent.putExtra("doctor", result.getDoctorName());
                        intent.putExtra("pdf_url", result.getPdfUrl());
                        startActivity(intent);
                    });
                    rvResults.setAdapter(adapter);
                    rvResults.setVisibility(View.VISIBLE);
                    findViewById(R.id.tvNoResults).setVisibility(View.GONE);
                } else {
                    showEmpty();
                }
            }

            @Override
            public void onFailure(Call<List<LabResult>> call, Throwable t) {
                Toast.makeText(LabResultsActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
                showEmpty();
            }
        });
    }

    private void showEmpty() {
        rvResults.setVisibility(View.GONE);
        findViewById(R.id.tvNoResults).setVisibility(View.VISIBLE);
    }
}
