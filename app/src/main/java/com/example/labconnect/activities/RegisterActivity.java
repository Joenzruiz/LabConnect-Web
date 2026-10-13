package com.example.labconnect.activities;

import android.app.AlertDialog;
import android.app.DatePickerDialog;
import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.ArrayAdapter;
import android.widget.AutoCompleteTextView;
import android.widget.Button;
import android.widget.ImageButton;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import com.example.labconnect.R;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.ApiResponse;
import com.example.labconnect.models.User;
import com.google.android.material.textfield.TextInputEditText;
import java.util.Calendar;
import java.util.Locale;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class RegisterActivity extends AppCompatActivity {

    private ImageButton btnBack;
    private TextInputEditText etFullName, etEmail, etMobile, etDob, etAddress, etPassword, etConfirmPassword, etResultPin;
    private AutoCompleteTextView actvGender;
    private Button btnRegister;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_register);

        btnBack         = findViewById(R.id.btnBack);
        etFullName      = findViewById(R.id.etFullName);
        etEmail         = findViewById(R.id.etEmail);
        etMobile        = findViewById(R.id.etMobile);
        etDob           = findViewById(R.id.etDob);
        etAddress       = findViewById(R.id.etAddress);
        etPassword      = findViewById(R.id.etPassword);
        etConfirmPassword = findViewById(R.id.etConfirmPassword);
        etResultPin     = findViewById(R.id.etResultPin);
        actvGender      = findViewById(R.id.actvGender);
        btnRegister     = findViewById(R.id.btnRegister);
        android.widget.TextView tvLogin = findViewById(R.id.tvLogin);

        tvLogin.setOnClickListener(v -> finish());
        btnBack.setOnClickListener(v -> finish());

        // Gender dropdown
        String[] genders = {"Male", "Female", "Other"};
        actvGender.setAdapter(new ArrayAdapter<>(this, android.R.layout.simple_dropdown_item_1line, genders));

        // Date picker
        etDob.setOnClickListener(v -> showDatePicker());
        etDob.setFocusable(false);

        btnRegister.setOnClickListener(v -> doRegister());
    }

    private void doRegister() {
        String name    = etFullName.getText().toString().trim();
        String email   = etEmail.getText().toString().trim().toLowerCase();
        String mobile  = etMobile.getText().toString().trim();
        String dob     = etDob.getText().toString().trim();
        String gender  = actvGender.getText().toString().trim();
        String address = etAddress.getText().toString().trim();
        String pin     = etResultPin.getText().toString().trim();
        String pass    = etPassword.getText().toString();
        String conf    = etConfirmPassword.getText().toString();

        // ── Client-side validation ──
        if (name.isEmpty())    { showError("Full name is required.");          return; }
        if (email.isEmpty())   { showError("Email address is required.");       return; }
        if (mobile.isEmpty())  { showError("Mobile number is required.");       return; }
        if (dob.isEmpty())     { showError("Date of birth is required. Tap the field to pick a date."); return; }
        if (gender.isEmpty())  { showError("Please select a gender.");          return; }
        if (address.isEmpty()) { showError("Address is required.");             return; }
        if (pass.isEmpty())    { showError("Password is required.");            return; }
        if (pass.length() < 8){ showError("Password must be at least 8 characters."); return; }
        if (!pass.equals(conf)){ showError("Passwords do not match.");          return; }
        if (pin.isEmpty())     { showError("4-digit Result PIN is required.");  return; }
        if (pin.length() != 4) { showError("Result PIN must be exactly 4 digits."); return; }

        btnRegister.setEnabled(false);
        btnRegister.setText("Registering…");

        User newUser = new User(name, email, mobile, dob, gender, address, pass, conf, pin);

        ApiClient.getApiService().register(newUser).enqueue(new Callback<ApiResponse>() {
            @Override
            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                btnRegister.setEnabled(true);
                btnRegister.setText("Register");

                if (response.isSuccessful() && response.body() != null) {
                    ApiResponse body = response.body();
                    if (body.isSuccess()) {
                        // Save session
                        getSharedPreferences("UserPrefs", MODE_PRIVATE).edit()
                            .putInt("userId", body.getUser().getId())
                            .putString("name",  name)
                            .putString("email", email)
                            .putString("pin",   pin)
                            .putBoolean("isLoggedIn", true)
                            .apply();

                        Toast.makeText(RegisterActivity.this, "Account created! Welcome, " + name.split(" ")[0] + "!", Toast.LENGTH_SHORT).show();
                        startActivity(new Intent(RegisterActivity.this, HomeActivity.class));
                        finishAffinity();
                    } else {
                        showError("Registration failed:\n" + body.getMessage());
                    }
                } else {
                    showError("Server returned error " + response.code() + ".\nMake sure XAMPP Apache is running.");
                }
            }

            @Override
            public void onFailure(Call<ApiResponse> call, Throwable t) {
                btnRegister.setEnabled(true);
                btnRegister.setText("Register");
                showError("Network error — cannot reach the server.\n\nDetails: " + t.getMessage()
                        + "\n\nMake sure Apache is running and the IP in ApiClient.java is correct.");
            }
        });
    }

    private void showError(String message) {
        new AlertDialog.Builder(this)
            .setTitle("Registration Error")
            .setMessage(message)
            .setPositiveButton("OK", null)
            .show();
    }

    private void showDatePicker() {
        Calendar c = Calendar.getInstance();
        new DatePickerDialog(this,
            (view, year, month, day) -> {
                String date = String.format(Locale.US, "%d-%02d-%02d", year, month + 1, day);
                etDob.setText(date);
            },
            c.get(Calendar.YEAR), c.get(Calendar.MONTH), c.get(Calendar.DAY_OF_MONTH)
        ).show();
    }
}
