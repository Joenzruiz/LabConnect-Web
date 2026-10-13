package com.example.labconnect.activities;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import androidx.appcompat.app.AppCompatActivity;
import com.example.labconnect.R;
import com.google.android.material.textfield.TextInputEditText;

public class LoginActivity extends AppCompatActivity {

    private TextInputEditText etEmail, etPassword;
    private Button btnLogin;
    private TextView tvForgotPassword, tvRegister;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_login);

        etEmail = findViewById(R.id.etEmail);
        etPassword = findViewById(R.id.etPassword);
        btnLogin = findViewById(R.id.btnLogin);
        tvForgotPassword = findViewById(R.id.tvForgotPassword);
        tvRegister = findViewById(R.id.tvRegister);

        btnLogin.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                String email = etEmail.getText().toString();
                String password = etPassword.getText().toString();

                if (email.isEmpty() || password.isEmpty()) {
                    android.widget.Toast.makeText(LoginActivity.this, "Please enter email and password", android.widget.Toast.LENGTH_SHORT).show();
                    return;
                }

                btnLogin.setEnabled(false);
                btnLogin.setText("Logging in...");

                com.example.labconnect.api.ApiClient.getApiService().login(email, password).enqueue(new retrofit2.Callback<com.example.labconnect.models.ApiResponse>() {
                    @Override
                    public void onResponse(retrofit2.Call<com.example.labconnect.models.ApiResponse> call, retrofit2.Response<com.example.labconnect.models.ApiResponse> response) {
                        btnLogin.setEnabled(true);
                        btnLogin.setText("Login");

                        if (response.isSuccessful() && response.body() != null) {
                            if (response.body().isSuccess()) {
                                android.widget.Toast.makeText(LoginActivity.this, "Login Successful", android.widget.Toast.LENGTH_SHORT).show();
                                getSharedPreferences("UserPrefs", MODE_PRIVATE).edit()
                                    .putInt("userId", response.body().getUser().getId())
                                    .putString("name", response.body().getUser().getFullName())
                                    .putString("email", response.body().getUser().getEmail())
                                    .putBoolean("isLoggedIn", true)
                                    .apply();
                                startActivity(new Intent(LoginActivity.this, HomeActivity.class));
                                finishAffinity();
                            } else {
                                System.out.println(response.body().getMessage());
                                android.widget.Toast.makeText(LoginActivity.this, response.body().getMessage(), android.widget.Toast.LENGTH_LONG).show();
                            }
                        } else {
                            System.out.println(response.body().getMessage());
                            android.widget.Toast.makeText(LoginActivity.this, "Server error. Please try again.", android.widget.Toast.LENGTH_LONG).show();
                        }
                    }

                    @Override
                    public void onFailure(retrofit2.Call<com.example.labconnect.models.ApiResponse> call, Throwable t) {
                        btnLogin.setEnabled(true);
                        btnLogin.setText("Login");
                        android.widget.Toast.makeText(LoginActivity.this, "Network error: " + t.getMessage(), android.widget.Toast.LENGTH_LONG).show();
                    }
                });
            }
        });
        tvRegister.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                startActivity(new Intent(LoginActivity.this, RegisterActivity.class));
            }
        });

        tvForgotPassword.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                startActivity(new Intent(LoginActivity.this, ForgotPasswordActivity.class));
            }
        });
    }
}
