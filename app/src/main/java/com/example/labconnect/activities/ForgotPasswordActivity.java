package com.example.labconnect.activities;

import android.os.Bundle;
import android.view.View;
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
 * Two-step Forgot Password flow:
 * Step 1 — User enters email → server generates OTP and sends it via Brevo email
 * Step 2 — User enters OTP + new password → server verifies OTP and updates password
 */
public class ForgotPasswordActivity extends AppCompatActivity {

    private View stepEmailPw, stepOtpPw;
    private TextInputEditText etEmail, etOtpCode, etNewPassword, etConfirmPassword;
    private MaterialButton btnSendOtp, btnResetPassword;
    private TextView tvSentTo, tvResendOtp;

    private String currentEmail = "";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_forgot_password);

        Toolbar toolbar = findViewById(R.id.toolbar);
        setSupportActionBar(toolbar);
        if (getSupportActionBar() != null) {
            getSupportActionBar().setDisplayHomeAsUpEnabled(true);
            getSupportActionBar().setDisplayShowTitleEnabled(false);
        }
        toolbar.setNavigationOnClickListener(v -> finish());

        stepEmailPw       = findViewById(R.id.stepEmailPw);
        stepOtpPw         = findViewById(R.id.stepOtpPw);
        etEmail           = findViewById(R.id.etEmail);
        etOtpCode         = findViewById(R.id.etOtpCode);
        etNewPassword     = findViewById(R.id.etNewPassword);
        etConfirmPassword = findViewById(R.id.etConfirmPassword);
        btnSendOtp        = findViewById(R.id.btnSendOtp);
        btnResetPassword  = findViewById(R.id.btnResetPassword);
        tvSentTo          = findViewById(R.id.tvSentTo);
        tvResendOtp       = findViewById(R.id.tvResendOtp);

        // Step 1 — Send OTP
        btnSendOtp.setOnClickListener(v -> {
            String email = etEmail.getText() != null
                    ? etEmail.getText().toString().trim().toLowerCase() : "";
            if (email.isEmpty()) {
                Toast.makeText(this, "Please enter your email address.", Toast.LENGTH_SHORT).show();
                return;
            }
            requestOtp(email);
        });

        // Step 2 — Verify OTP + reset password
        btnResetPassword.setOnClickListener(v -> {
            String code    = etOtpCode.getText()        != null ? etOtpCode.getText().toString().trim() : "";
            String newPw   = etNewPassword.getText()    != null ? etNewPassword.getText().toString() : "";
            String confirm = etConfirmPassword.getText()!= null ? etConfirmPassword.getText().toString() : "";

            if (code.isEmpty()) {
                Toast.makeText(this, "Please enter the verification code.", Toast.LENGTH_SHORT).show();
                return;
            }
            if (newPw.length() < 6) {
                Toast.makeText(this, "Password must be at least 6 characters.", Toast.LENGTH_SHORT).show();
                return;
            }
            if (!newPw.equals(confirm)) {
                Toast.makeText(this, "Passwords do not match.", Toast.LENGTH_SHORT).show();
                return;
            }
            verifyOtpAndReset(code, newPw);
        });

        tvResendOtp.setOnClickListener(v -> requestOtp(currentEmail));
    }

    // ── Step 1 ─────────────────────────────────────────────────────────────────
    private void requestOtp(String email) {
        btnSendOtp.setEnabled(false);
        btnSendOtp.setText("Sending...");

        Map<String, String> body = new HashMap<>();
        body.put("email", email);
        body.put("type", "password");

        ApiClient.getApiService().sendOtp(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnSendOtp.setEnabled(true);
                btnSendOtp.setText("Send Verification Code");

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    currentEmail = email;

                    tvSentTo.setText("A 6-digit code was sent to " + email);
                    stepEmailPw.setVisibility(View.GONE);
                    stepOtpPw.setVisibility(View.VISIBLE);
                    Toast.makeText(ForgotPasswordActivity.this,
                            response.body().getMessage(), Toast.LENGTH_LONG).show();
                } else {
                    String msg = (response.body() != null) ? response.body().getMessage() : "Email not found.";
                    Toast.makeText(ForgotPasswordActivity.this, msg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnSendOtp.setEnabled(true);
                btnSendOtp.setText("Send Verification Code");
                Toast.makeText(ForgotPasswordActivity.this,
                        "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    // ── Step 2 ─────────────────────────────────────────────────────────────────
    private void verifyOtpAndReset(String code, String newPassword) {
        btnResetPassword.setEnabled(false);
        btnResetPassword.setText("Resetting...");

        Map<String, String> body = new HashMap<>();
        body.put("email",        currentEmail);
        body.put("otp_code",     code);
        body.put("type",         "password");
        body.put("new_password", newPassword);

        ApiClient.getApiService().verifyOtp(body).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnResetPassword.setEnabled(true);
                btnResetPassword.setText("Reset Password");

                if (response.isSuccessful() && response.body() != null && response.body().isSuccess()) {
                    Toast.makeText(ForgotPasswordActivity.this,
                            "Password reset successfully! Please login with your new password.",
                            Toast.LENGTH_LONG).show();
                    finish();
                } else {
                    String msg = (response.body() != null) ? response.body().getMessage() : "Invalid code.";
                    Toast.makeText(ForgotPasswordActivity.this, msg, Toast.LENGTH_LONG).show();
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnResetPassword.setEnabled(true);
                btnResetPassword.setText("Reset Password");
                Toast.makeText(ForgotPasswordActivity.this,
                        "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
