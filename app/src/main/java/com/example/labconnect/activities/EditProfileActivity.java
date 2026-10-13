package com.example.labconnect.activities;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.widget.Button;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import com.example.labconnect.R;
import com.google.android.material.textfield.TextInputEditText;

public class EditProfileActivity extends AppCompatActivity {
    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_edit_profile);
        
        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);

        android.widget.ImageButton btnBack = findViewById(R.id.btnBack);
        if (btnBack != null) btnBack.setOnClickListener(v -> finish());
        
        TextInputEditText etName = findViewById(R.id.etName);
        TextInputEditText etEmail = findViewById(R.id.etEmail);
        TextInputEditText etMobile = findViewById(R.id.etMobile);
        TextInputEditText etAddress = findViewById(R.id.etAddress);
        TextInputEditText etDob = findViewById(R.id.etDob);
        android.widget.AutoCompleteTextView actvGender = findViewById(R.id.actvGender);
        Button btnSave = findViewById(R.id.btnSave);

        // Pre-fill with current data
        etName.setText(prefs.getString("name", ""));
        etEmail.setText(prefs.getString("email", ""));
        etMobile.setText(prefs.getString("mobile", ""));
        etAddress.setText(prefs.getString("address", ""));
        etDob.setText(prefs.getString("dob", ""));
        actvGender.setText(prefs.getString("gender", "Female"), false);

        // Gender Dropdown
        String[] genders = new String[]{"Male", "Female", "Other"};
        android.widget.ArrayAdapter<String> adapter = new android.widget.ArrayAdapter<>(this, android.R.layout.simple_dropdown_item_1line, genders);
        actvGender.setAdapter(adapter);

        etDob.setOnClickListener(v -> showDatePicker(etDob));
        
        btnSave.setOnClickListener(v -> {
            String newName = etName.getText().toString();
            String newEmail = etEmail.getText().toString();
            String newMobile = etMobile.getText().toString();
            String newAddress = etAddress.getText().toString();
            String newDob = etDob.getText().toString();
            String newGender = actvGender.getText().toString();

            if (newName.isEmpty()) {
                Toast.makeText(this, "Name cannot be empty", Toast.LENGTH_SHORT).show();
                return;
            }

            prefs.edit()
                    .putString("name", newName)
                    .putString("email", newEmail)
                    .putString("mobile", newMobile)
                    .putString("address", newAddress)
                    .putString("dob", newDob)
                    .putString("gender", newGender)
                    .apply();

            Toast.makeText(this, "Profile updated", Toast.LENGTH_SHORT).show();
            finish();
        });
    }

    private void showDatePicker(TextInputEditText et) {
        java.util.Calendar c = java.util.Calendar.getInstance();
        new android.app.DatePickerDialog(this, (view, year, month, dayOfMonth) -> {
            String date = String.format(java.util.Locale.getDefault(), "%02d/%02d/%d", month + 1, dayOfMonth, year);
            et.setText(date);
        }, c.get(java.util.Calendar.YEAR), c.get(java.util.Calendar.MONTH), c.get(java.util.Calendar.DAY_OF_MONTH)).show();
    }
}
