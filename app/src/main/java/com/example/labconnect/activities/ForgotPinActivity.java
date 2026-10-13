package com.example.labconnect.activities;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
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
import com.google.android.material.textfield.TextInputEditText;
import java.util.HashMap;
import java.util.Map;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

/**
 * Two-step Forgot PIN flow:
 * Step 1 — User enters email → server generates OTP and sends it via Brevo email
 * Step 2 — User enters OTP + new PIN → server verifies OTP → app saves new PIN locally
 */
public class ForgotPinActivity extends AppCompatActivity {

    private View stepEmail, stepOtp;
    private TextInputEditText etEmailPin, etPinOtpCode;
    private EditText etResetPin;
    private MaterialButton btnSendPinOtp, btnResetPin;
    private TextView tvPinOtpSentTo, tvResendPinOtp;
    private View[] rdots;

    private String currentEmail = "";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_forgot_pin);

        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowTitleEnabled(false);
        }
        toolbar.setNavigationOnClickListener(v -> finish());

        stepEmail     = findViewById(R.id.stepEmail);
        stepOtp       = findViewById(R.id.stepOtp);
        etEmailPin    = findViewById(R.id.etEmailPin);
        etPinOtpCode  = findViewById(R.id.etPinOtpCode);
        etResetPin    = findViewById(R.id.etResetPin);
        btnSendPinOtp = findViewById(R.id.btnSendPinOtp);
        btnResetPin   = findViewById(R.id.btnResetPin);
        tvPinOtpSentTo= findViewById(R.id.tvPinOtpSentTo);
        tvResendPinOtp= findViewById(R.id.tvResendPinOtp);

        // Pre-fill email if patient is logged in
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        String savedEmail = prefs.getString("email", "");
        if (!savedEmail.isEmpty() && etEmailPin != null) {
            etEmailPin.setText(savedEmail);
        }

        // Step 1 — Send OTP
        btnSendPinOtp.setOnClickListener(v -> {
            String email = etEmailPin.getText() != null
                    ? etEmailPin.getText().toString().trim().toLowerCase() : "";
            if (email.isEmpty()) {
                Toast.makeText(this, "Please enter your email address.", Toast.LENGTH_SHORT).show();
                return;
            }
            requestOtp(email);
        });

        // Step 2 — Verify OTP + save new PIN
        btnResetPin.setOnClickListener(v -> {
            String code   = etPinOtpCode.getText() != null ? etPinOtpCode.getText().toString().trim() : "";
            String newPin = etResetPin.getText().toString();

            if (code.isEmpty()) {
                Toast.makeText(this, "Please enter the verification code.", Toast.LENGTH_SHORT).show();
                return;
            }
            if (newPin.length() != 4) {
                Toast.makeText(this, "New PIN must be exactly 4 digits.", Toast.LENGTH_SHORT).show();
                return;
            }
            verifyOtpAndReset(code, newPin);
        });

        // Resend
        tvResendPinOtp.setOnClickListener(v -> requestOtp(currentEmail));
    }

    // ── Step 1: Request OTP (server sends email via Brevo) ──────────────────────
    private void requestOtp(String email) {
        btnSendPinOtp.setEnabled(false);
        btnSendPinOtp.setText("Sending...");

        Map<String, String> body = new HashMap<>();
        body.put("email", email);
        body.put("type", "pin");

        ApiClient.getApiService().sendOtp(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnSendPinOtp.setEnabled(true);
                btnSendPinOtp.setText("Send Verification Code");

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    currentEmail = email;

                    // Show step 2
                    tvPinOtpSentTo.setText("A 6-digit code was sent to " + email);
                    stepEmail.setVisibility(View.GONE);
                    stepOtp.setVisibility(View.VISIBLE);
                    Toast.makeText(ForgotPinActivity.this,
                            response.body().getMessage(), Toast.LENGTH_LONG).show();
                } else {
                    String msg = (response.body() != null) ? response.body().getMessage() : "Email not found.";
                    Toast.makeText(ForgotPinActivity.this, msg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnSendPinOtp.setEnabled(true);
                btnSendPinOtp.setText("Send Verification Code");
                Toast.makeText(ForgotPinActivity.this,
                        "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    // ── Step 2: Verify OTP with server, then save PIN locally ──────────────────
    private void verifyOtpAndReset(String code, String newPin) {
        btnResetPin.setEnabled(false);
        btnResetPin.setText("Verifying...");

        Map<String, String> body = new HashMap<>();
        body.put("email", currentEmail);
        body.put("otp_code", code);
        body.put("type", "pin");
        body.put("new_pin", newPin);

        ApiClient.getApiService().verifyOtp(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnResetPin.setEnabled(true);
                btnResetPin.setText("Reset PIN");

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    // Save new PIN locally
                    getSharedPreferences("UserPrefs", MODE_PRIVATE)
                            .edit().putString("pin", newPin).apply();
                    Toast.makeText(ForgotPinActivity.this,
                            "PIN reset successfully!", Toast.LENGTH_SHORT).show();
                    finish();
                } else {
                    String msg = (response.body() != null) ? response.body().getMessage() : "Invalid code.";
                    Toast.makeText(ForgotPinActivity.this, msg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnResetPin.setEnabled(true);
                btnResetPin.setText("Reset PIN");
                Toast.makeText(ForgotPinActivity.this,
                        "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void updateDots(View[] dotViews, int filled) {
        for (int i = 0; i < dotViews.length; i++) {
            dotViews[i].setBackgroundResource(i < filled
                    ? R.drawable.pin_dot_filled
                    : R.drawable.pin_dot_empty);
        }
    }

    private void showKeyboard(EditText field) {
        field.requestFocus();
        InputMethodManager imm = (InputMethodManager) getSystemService(INPUT_METHOD_SERVICE);
        if (imm != null) imm.showSoftInput(field, InputMethodManager.SHOW_IMPLICIT);
    }
}
