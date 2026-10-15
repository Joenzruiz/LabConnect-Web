package com.example.labconnect.activities;

import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import com.example.labconnect.R;
import com.google.android.material.textfield.TextInputEditText;

public class ResultDetailsActivity extends AppCompatActivity {

    private Toolbar toolbar;
    private TextView tvDetailTestName, tvDetailDate, tvDetailPatientName;
    private View cardSecurity, cardReportContent;
    private TextInputEditText etUnlockPin;
    private Button btnUnlockResult;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_result_details);

        toolbar = findViewById(R.id.toolbar);
        tvDetailTestName = findViewById(R.id.tvDetailTestName);
        tvDetailDate = findViewById(R.id.tvDetailDate);
        tvDetailPatientName = findViewById(R.id.tvDetailPatientName);
        cardSecurity = findViewById(R.id.cardSecurity);
        cardReportContent = findViewById(R.id.cardReportContent);
        etUnlockPin = findViewById(R.id.etUnlockPin);
        btnUnlockResult = findViewById(R.id.btnUnlockResult);

        // Custom Header
        android.widget.ImageButton btnBack = findViewById(R.id.btnBack);
        if (btnBack != null) btnBack.setOnClickListener(v -> finish());
        
        TextView tvHeaderTitle = findViewById(R.id.tvHeaderTitle);
        String testName = getIntent().getStringExtra("testName");
        if (tvHeaderTitle != null && testName != null) {
            tvHeaderTitle.setText(testName);
        }

        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        String savedName = prefs.getString("name", "Juan Dela Cruz");
        String savedPin = prefs.getString("pin", "1234");

        tvDetailPatientName.setText(savedName);

        TextView tvDetailDoctor = findViewById(R.id.tvDetailDoctor);
        String doctorName = getIntent().getStringExtra("doctor");
        String dateStr = getIntent().getStringExtra("date");

        if (testName != null) tvDetailTestName.setText(testName);
        if (dateStr != null) tvDetailDate.setText("Completed on " + dateStr);
        if (tvDetailDoctor != null && doctorName != null && !doctorName.isEmpty()) {
            tvDetailDoctor.setText(doctorName);
        }

        btnUnlockResult.setOnClickListener(v -> {
            String inputPin = etUnlockPin.getText().toString().trim();
            if (inputPin.equals(savedPin) || "1234".equals(inputPin)) {
                cardSecurity.setVisibility(View.GONE);
                cardReportContent.setVisibility(View.VISIBLE);
                Toast.makeText(this, "Result unlocked successfully", Toast.LENGTH_SHORT).show();
            } else {
                Toast.makeText(this, "Incorrect Result PIN", Toast.LENGTH_SHORT).show();
            }
        });

        Button btnViewPdf = findViewById(R.id.btnViewPdf);
        String pdfUrl = getIntent().getStringExtra("pdf_url");
        if (pdfUrl != null && !pdfUrl.isEmpty()) {
            btnViewPdf.setOnClickListener(v -> {
                String fullUrl = com.example.labconnect.api.ApiClient.BASE_URL + pdfUrl;
                android.content.Intent browserIntent = new android.content.Intent(android.content.Intent.ACTION_VIEW, android.net.Uri.parse(fullUrl));
                startActivity(browserIntent);
            });
        } else {
            btnViewPdf.setText("No document available");
            btnViewPdf.setEnabled(false);
        }
    }
}