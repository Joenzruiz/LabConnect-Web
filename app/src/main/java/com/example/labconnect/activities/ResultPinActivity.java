package com.example.labconnect.activities;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.view.inputmethod.InputMethodManager;
import android.widget.EditText;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import com.example.labconnect.R;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.ApiResponse;
import com.google.android.material.button.MaterialButton;
import java.util.HashMap;
import java.util.Map;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class ResultPinActivity extends AppCompatActivity {

    private EditText etPin, etNewPin;
    private MaterialButton btnVerify;
    private TextView tvTitle, tvDesc, tvForgotPin, tvCurrentPinLabel;
    private View newPinSection;
    private boolean isChangeMode = false;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_result_pin);

        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 0);

        isChangeMode = getIntent().getBooleanExtra("isChangeMode", false);

        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayShowTitleEnabled(false);
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
        }
        toolbar.setNavigationOnClickListener(v -> finish());

        etPin             = findViewById(R.id.etPin);
        etNewPin          = findViewById(R.id.etNewPin);
        btnVerify         = findViewById(R.id.btnVerify);
        tvTitle           = findViewById(R.id.tvPinTitle);
        tvDesc            = findViewById(R.id.tvPinDesc);
        tvForgotPin       = findViewById(R.id.tvForgotPin);
        newPinSection     = findViewById(R.id.newPinSection);
        tvCurrentPinLabel = findViewById(R.id.tvCurrentPinLabel);

        if (isChangeMode) {
            tvTitle.setText("Change Result PIN");
            tvDesc.setText("Enter your current PIN, then enter your new 4-digit PIN.");
            btnVerify.setText("Update PIN");
            newPinSection.setVisibility(View.VISIBLE);
            tvCurrentPinLabel.setVisibility(View.VISIBLE);
            tvForgotPin.setVisibility(View.GONE);
        }

        // Focus and open keyboard
        etPin.requestFocus();
        etPin.postDelayed(() -> {
            InputMethodManager imm = (InputMethodManager) getSystemService(INPUT_METHOD_SERVICE);
            if (imm != null) {
                imm.showSoftInput(etPin, InputMethodManager.SHOW_IMPLICIT);
            }
        }, 200);

        btnVerify.setOnClickListener(v -> {
            String pinInput = etPin.getText().toString().trim();

            if (pinInput.isEmpty()) {
                Toast.makeText(this, "Please enter your 4-digit PIN.", Toast.LENGTH_SHORT).show();
                return;
            }

            if (isChangeMode) {
                String newPin = etNewPin.getText().toString().trim();
                if (newPin.length() != 4) {
                    Toast.makeText(this, "New PIN must be exactly 4 digits.", Toast.LENGTH_SHORT).show();
                    return;
                }

                btnVerify.setEnabled(false);
                btnVerify.setText("Updating...");

                Map<String, Object> body = new HashMap<>();
                body.put("patient_id", userId);
                body.put("current_pin", pinInput);
                body.put("new_pin", newPin);
                body.put("action", "update");

                ApiClient.getApiService().verifyPin(body).enqueue(new Callback<ApiResponse>() {
                    @Override
                    public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                        btnVerify.setEnabled(true);
                        btnVerify.setText("Update PIN");

                        if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                            prefs.edit().putString("pin", newPin).apply();
                            Toast.makeText(ResultPinActivity.this, "PIN updated successfully!", Toast.LENGTH_SHORT).show();
                            finish();
                        } else {
                            String msg = (response.body() != null) ? response.body().getMessage() : "Incorrect current PIN.";
                            Toast.makeText(ResultPinActivity.this, msg, Toast.LENGTH_SHORT).show();
                            etPin.setText("");
                            etPin.requestFocus();
                        }
                    }

                    @Override
                    public void onFailure(Call<ApiResponse> call, Throwable t) {
                        btnVerify.setEnabled(true);
                        btnVerify.setText("Update PIN");
                        // Fallback check locally
                        String localPin = prefs.getString("pin", "1234");
                        if (pinInput.equals(localPin)) {
                            prefs.edit().putString("pin", newPin).apply();
                            Toast.makeText(ResultPinActivity.this, "PIN updated!", Toast.LENGTH_SHORT).show();
                            finish();
                        } else {
                            Toast.makeText(ResultPinActivity.this, "Incorrect current PIN.", Toast.LENGTH_SHORT).show();
                        }
                    }
                });

            } else {
                // Verify action against server
                btnVerify.setEnabled(false);
                btnVerify.setText("Verifying...");

                Map<String, Object> body = new HashMap<>();
                body.put("patient_id", userId);
                body.put("pin", pinInput);
                body.put("action", "verify");

                ApiClient.getApiService().verifyPin(body).enqueue(new Callback<ApiResponse>() {
                    @Override
                    public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                        btnVerify.setEnabled(true);
                        btnVerify.setText("Verify PIN");

                        if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                            // Update local SharedPreferences with confirmed PIN
                            prefs.edit().putString("pin", pinInput).apply();
                            setResult(RESULT_OK);
                            finish();
                        } else {
                            // Fallback to local SharedPreferences check if offline or default
                            String localPin = prefs.getString("pin", "1234");
                            if (pinInput.equals(localPin)) {
                                setResult(RESULT_OK);
                                finish();
                            } else {
                                Toast.makeText(ResultPinActivity.this, "Incorrect PIN. Please try again.", Toast.LENGTH_SHORT).show();
                                etPin.setText("");
                                etPin.requestFocus();
                            }
                        }
                    }

                    @Override
                    public void onFailure(Call<ApiResponse> call, Throwable t) {
                        btnVerify.setEnabled(true);
                        btnVerify.setText("Verify PIN");
                        // Fallback check
                        String localPin = prefs.getString("pin", "1234");
                        if (pinInput.equals(localPin)) {
                            setResult(RESULT_OK);
                            finish();
                        } else {
                            Toast.makeText(ResultPinActivity.this, "Incorrect PIN.", Toast.LENGTH_SHORT).show();
                            etPin.setText("");
                            etPin.requestFocus();
                        }
                    }
                });
            }
        });

        // Forgot PIN → go to ForgotPinActivity
        tvForgotPin.setOnClickListener(v -> {
            Intent intent = new Intent(this, ForgotPinActivity.class);
            startActivity(intent);
        });
    }
}
