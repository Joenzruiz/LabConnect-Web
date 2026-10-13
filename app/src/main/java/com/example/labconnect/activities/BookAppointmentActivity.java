package com.example.labconnect.activities;

import android.content.Intent;
import android.content.SharedPreferences;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;
import androidx.appcompat.app.AppCompatActivity;
import androidx.appcompat.widget.Toolbar;
import com.example.labconnect.R;
import com.example.labconnect.adapters.LabTestAdapter;
import com.example.labconnect.adapters.TimeSlotAdapter;
import com.example.labconnect.adapters.DoctorAdapter;
import com.example.labconnect.adapters.DateAdapter;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.Appointment;
import com.example.labconnect.models.LabTest;
import com.example.labconnect.models.Doctor;
import com.google.android.material.textfield.TextInputEditText;
import com.google.android.material.bottomnavigation.BottomNavigationView;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.Locale;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

import android.widget.ViewFlipper;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.GridLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

public class BookAppointmentActivity extends AppCompatActivity {

    private ViewFlipper viewFlipper;
    private RecyclerView rvDiagnostics, rvXray, rvUltrasound, rvDoctors, rvDates, rvTimeSlots;
    private TextView tvStepIndicator, tvTimer, tvDateNote;
    private View step1Bar, step2Bar, step3Bar;
    private TextInputEditText etDate, etTime;
    private Button btnBack, btnNext;
    private Toolbar toolbar;
    private BottomNavigationView bottomNavigation;

    private LabTestAdapter diagnosticsAdapter, xrayAdapter, ultrasoundAdapter;
    private DoctorAdapter doctorAdapter;
    private DateAdapter dateAdapter;
    private TimeSlotAdapter timeSlotAdapter;

    private int currentStep = 0;
    private LabTest selectedTest;
    private Doctor selectedDoctor;
    private Calendar selectedDate;
    private String selectedTime;
    private android.os.CountDownTimer bookingTimer;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_book_appointment);

        toolbar = findViewById(R.id.toolbar);
        viewFlipper = findViewById(R.id.viewFlipper);
        rvDiagnostics = findViewById(R.id.rvDiagnostics);
        rvXray = findViewById(R.id.rvXray);
        rvUltrasound = findViewById(R.id.rvUltrasound);
        rvDoctors = findViewById(R.id.rvDoctors);
        rvDates = findViewById(R.id.rvDates);
        rvTimeSlots = findViewById(R.id.rvTimeSlots);
        tvStepIndicator = findViewById(R.id.tvStepIndicator);
        tvTimer = findViewById(R.id.tvTimer);
        tvDateNote = findViewById(R.id.tvDateNote);
        step1Bar = findViewById(R.id.step1Bar);
        step2Bar = findViewById(R.id.step2Bar);
        step3Bar = findViewById(R.id.step3Bar);
        etDate = findViewById(R.id.etDate);
        etTime = findViewById(R.id.etTime);
        btnBack = findViewById(R.id.btnBack);
        btnNext = findViewById(R.id.btnNext);
        bottomNavigation = findViewById(R.id.bottomNavigation);

        // Custom header back button
        android.widget.ImageButton btnHeaderBack = findViewById(R.id.btnHeaderBack);
        if (btnHeaderBack != null) btnHeaderBack.setOnClickListener(v -> handleBackPress());

        rvDiagnostics.setLayoutManager(new LinearLayoutManager(this));
        rvXray.setLayoutManager(new LinearLayoutManager(this));
        rvUltrasound.setLayoutManager(new LinearLayoutManager(this));
        
        rvDoctors.setLayoutManager(new LinearLayoutManager(this));
        rvDates.setLayoutManager(new GridLayoutManager(this, 4));
        rvTimeSlots.setLayoutManager(new GridLayoutManager(this, 3));

        // Date and time grids are always visible in the new design


        btnNext.setOnClickListener(v -> handleNext());
        btnBack.setOnClickListener(v -> handleBack());

        loadTests();
        setupBottomNavigation();

        btnNext.setEnabled(false);
        findViewById(R.id.tvHistory).setOnClickListener(v -> startActivity(new Intent(this, MyAppointmentsActivity.class)));
    }

    private void setupBottomNavigation() {
        bottomNavigation.setSelectedItemId(R.id.nav_appointments);
        bottomNavigation.setOnItemSelectedListener(item -> {
            int id = item.getItemId();
            if (id == R.id.nav_home) {
                startActivity(new Intent(this, HomeActivity.class));
                return true;
            }
            if (id == R.id.nav_appointments) return true;
            if (id == R.id.nav_results) {
                startActivity(new Intent(this, LabResultsActivity.class));
                return true;
            }
            if (id == R.id.nav_profile) {
                startActivity(new Intent(this, ProfileActivity.class));
                return true;
            }
            return false;
        });
    }

    private void handleNext() {
        if (currentStep == 0 && selectedTest == null) {
            Toast.makeText(this, "Please select a test", Toast.LENGTH_SHORT).show();
            return;
        }
        if (currentStep == 1 && (selectedDoctor == null || selectedDate == null || selectedTime == null)) {
            Toast.makeText(this, "Please complete your schedule selection", Toast.LENGTH_SHORT).show();
            return;
        }

        if (currentStep < 2) {
            currentStep++;
            viewFlipper.setDisplayedChild(currentStep);
            updateStepUI();
        } else {
            bookAppointment();
        }
    }

    private void handleBack() {
        if (currentStep > 0) {
            currentStep--;
            viewFlipper.setDisplayedChild(currentStep);
            updateStepUI();
        }
    }

    private void handleBackPress() {
        if (currentStep > 0) {
            handleBack();
        } else {
            finish();
        }
    }

    private void updateStepUI() {
        boolean isSuccess = currentStep == 3;
        View headerContainer = findViewById(R.id.headerContainer);
        if (headerContainer != null) headerContainer.setVisibility(isSuccess ? View.GONE : View.VISIBLE);
        
        View bottomBar = (View) btnNext.getParent();
        if (bottomBar != null) bottomBar.setVisibility(isSuccess ? View.GONE : View.VISIBLE);
        
        btnBack.setVisibility(currentStep > 0 && currentStep < 3 ? View.VISIBLE : View.GONE);
        btnNext.setText(currentStep == 2 ? "Confirm booking" : "Continue");
        
        step1Bar.setBackgroundTintList(android.content.res.ColorStateList.valueOf(currentStep >= 0 ? getResources().getColor(R.color.colorPrimary) : 0xFFE2E8F0));
        step2Bar.setBackgroundTintList(android.content.res.ColorStateList.valueOf(currentStep >= 1 ? getResources().getColor(R.color.colorPrimary) : 0xFFE2E8F0));
        step3Bar.setBackgroundTintList(android.content.res.ColorStateList.valueOf(currentStep >= 2 ? getResources().getColor(R.color.colorPrimary) : 0xFFE2E8F0));

        String stepText = "";
        switch (currentStep) {
            case 0: stepText = "Step 1 of 3 • Test"; break;
            case 1: 
                stepText = "Step 2 of 3 • Schedule"; 
                setupScheduleStep();
                break;
            case 2: stepText = "Step 3 of 3 • Confirm"; break;
        }
        tvStepIndicator.setText(stepText);

        if (currentStep == 2) {
            populateSummary();
            if (tvTimer != null) {
                tvTimer.setVisibility(View.VISIBLE);
                startBookingTimer();
            }
        }

        if (isSuccess) {
            TextView tvSuccessSummary = findViewById(R.id.tvSuccessSummary);
            TextView tvSuccessInstructions = findViewById(R.id.tvSuccessInstructions);
            
            if (tvSuccessSummary != null && selectedDate != null && selectedDoctor != null && selectedTest != null) {
                String summary = String.format(Locale.getDefault(), 
                    "%s with %s on %tA, %tB %td, %tY at %s. You will be notified once the laboratory approves it.",
                    selectedTest.getName(), selectedDoctor.getName(), 
                    selectedDate, selectedDate, selectedDate, selectedDate, selectedTime);
                tvSuccessSummary.setText(summary);
            }
            if (tvSuccessInstructions != null && selectedTest != null) {
                tvSuccessInstructions.setText(selectedTest.getInstructions());
            }

            Button btnSuccessViewAppts = findViewById(R.id.btnSuccessViewAppts);
            Button btnSuccessBookAnother = findViewById(R.id.btnSuccessBookAnother);
            
            if (btnSuccessViewAppts != null) {
                btnSuccessViewAppts.setOnClickListener(v -> {
                    startActivity(new Intent(this, MyAppointmentsActivity.class));
                    finish();
                });
            }
            
            if (btnSuccessBookAnother != null) {
                btnSuccessBookAnother.setOnClickListener(v -> {
                    currentStep = 0;
                    viewFlipper.setDisplayedChild(0);
                    updateStepUI();
                    selectedTest = null;
                    btnNext.setEnabled(false);
                    btnNext.setBackgroundTintList(android.content.res.ColorStateList.valueOf(0xFFCBD5E0));
                });
            }
        }
    }

    private void startBookingTimer() {
        if (bookingTimer != null) bookingTimer.cancel();
        
        bookingTimer = new android.os.CountDownTimer(119000, 1000) {
            @Override
            public void onTick(long millisUntilFinished) {
                long minutes = (millisUntilFinished / 1000) / 60;
                long seconds = (millisUntilFinished / 1000) % 60;
                tvTimer.setText(String.format(Locale.getDefault(), 
                    "Slot held for %d:%02d — confirm before the timer ends.", minutes, seconds));
            }

            @Override
            public void onFinish() {
                tvTimer.setText("Slot expired. Please try again.");
                btnNext.setEnabled(false);
                btnNext.setBackgroundTintList(android.content.res.ColorStateList.valueOf(0xFFCBD5E0));
            }
        }.start();
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (bookingTimer != null) bookingTimer.cancel();
    }

    private void onTestSelected(LabTest test) {
        selectedTest = test;
        if (diagnosticsAdapter != null) diagnosticsAdapter.setSelectedTest(test);
        if (xrayAdapter != null) xrayAdapter.setSelectedTest(test);
        if (ultrasoundAdapter != null) ultrasoundAdapter.setSelectedTest(test);
        
        btnNext.setBackgroundTintList(android.content.res.ColorStateList.valueOf(getResources().getColor(R.color.colorPrimary)));
        btnNext.setEnabled(true);
    }

    private void setupScheduleStep() {
        // Reset selections for Step 2
        selectedDoctor = null;
        selectedDate = null;
        selectedTime = null;
        btnNext.setEnabled(false);
        btnNext.setBackgroundTintList(android.content.res.ColorStateList.valueOf(0xFFCBD5E0));

        // Setup Doctors from API
        ApiClient.getApiService().getDoctors().enqueue(new retrofit2.Callback<List<Doctor>>() {
            @Override
            public void onResponse(retrofit2.Call<List<Doctor>> call, retrofit2.Response<List<Doctor>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    List<Doctor> allDoctors = response.body();
                    List<Doctor> filteredDoctors = new ArrayList<>();
                    
                    // Filter based on test category (very basic mapping for demo)
                    String testName = selectedTest.getName().toLowerCase();
                    for (Doctor d : allDoctors) {
                        String spec = d.getSpecialty().toLowerCase();
                        if (testName.contains("x-ray") && spec.contains("radiolog")) {
                            filteredDoctors.add(d);
                        } else if (testName.contains("ultrasound") && (spec.contains("sonolog") || spec.contains("radiolog"))) {
                            filteredDoctors.add(d);
                        } else if (!testName.contains("x-ray") && !testName.contains("ultrasound") && !spec.contains("radiolog") && !spec.contains("sonolog")) {
                            filteredDoctors.add(d);
                        }
                    }
                    
                    // If no match found by specialty, just show all to prevent empty list
                    if (filteredDoctors.isEmpty()) {
                        filteredDoctors.addAll(allDoctors);
                    }

                    doctorAdapter = new DoctorAdapter(filteredDoctors, doctor -> {
                        selectedDoctor = doctor;
                        tvDateNote.setText("Dimmed dates are days " + doctor.getName() + " is not on confirmed duty.");
                        selectedDate = null;
                        selectedTime = null;
                        checkScheduleCompletion();
                        fetchDoctorSchedule(doctor.getId());
                    });
                    rvDoctors.setAdapter(doctorAdapter);
                } else {
                    Toast.makeText(BookAppointmentActivity.this, "Could not load doctors", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(retrofit2.Call<List<Doctor>> call, Throwable t) {
                Toast.makeText(BookAppointmentActivity.this, "Network error loading doctors", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void fetchDoctorSchedule(String doctorId) {
        ApiClient.getApiService().getAvailableSchedule(doctorId).enqueue(new retrofit2.Callback<java.util.Map<String, List<String>>>() {
            @Override
            public void onResponse(retrofit2.Call<java.util.Map<String, List<String>>> call, retrofit2.Response<java.util.Map<String, List<String>>> response) {
                if (response.isSuccessful() && response.body() != null) {
                    java.util.Map<String, List<String>> schedule = response.body();
                    
                    List<Calendar> dates = new ArrayList<>();
                    // We'll just display dates that are returned by the API since those are the available ones.
                    // If no dates returned, we show empty.
                    for (String dateStr : schedule.keySet()) {
                        try {
                            java.util.Date d = new java.text.SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).parse(dateStr);
                            Calendar cal = Calendar.getInstance();
                            cal.setTime(d);
                            dates.add(cal);
                        } catch (Exception e) {}
                    }
                    
                    dateAdapter = new DateAdapter(dates, date -> {
                        selectedDate = date;
                        selectedTime = null;
                        checkScheduleCompletion();
                        
                        // Populate times
                        String key = new java.text.SimpleDateFormat("yyyy-MM-dd", Locale.getDefault()).format(date.getTime());
                        List<String> times = schedule.get(key);
                        if (times == null) times = new ArrayList<>();
                        
                        timeSlotAdapter = new TimeSlotAdapter(times, time -> {
                            selectedTime = time;
                            checkScheduleCompletion();
                        });
                        rvTimeSlots.setAdapter(timeSlotAdapter);
                    });
                    rvDates.setAdapter(dateAdapter);
                    
                    // Clear times
                    timeSlotAdapter = new TimeSlotAdapter(new ArrayList<>(), time -> {});
                    rvTimeSlots.setAdapter(timeSlotAdapter);
                    
                } else {
                    Toast.makeText(BookAppointmentActivity.this, "Could not load schedule", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(retrofit2.Call<java.util.Map<String, List<String>>> call, Throwable t) {
                Toast.makeText(BookAppointmentActivity.this, "Network error loading schedule", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void checkScheduleCompletion() {
        if (selectedDoctor != null && selectedDate != null && selectedTime != null) {
            btnNext.setBackgroundTintList(android.content.res.ColorStateList.valueOf(getResources().getColor(R.color.colorPrimary)));
            btnNext.setEnabled(true);
        }
    }

    private void loadTests() {
        ApiClient.getApiService().getTests().enqueue(new retrofit2.Callback<List<LabTest>>() {
            @Override
            public void onResponse(retrofit2.Call<List<LabTest>> call, retrofit2.Response<List<LabTest>> response) {
                if (response.isSuccessful() && response.body() != null && !response.body().isEmpty()) {
                    displayTests(response.body());
                } else {
                    Toast.makeText(BookAppointmentActivity.this, "No tests found in database.", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(retrofit2.Call<List<LabTest>> call, Throwable t) {
                Toast.makeText(BookAppointmentActivity.this, "Network error loading tests: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }

    private void displayTests(List<LabTest> allTests) {
        List<LabTest> diagnostics = new ArrayList<>();
        List<LabTest> xrays = new ArrayList<>();
        List<LabTest> ultrasounds = new ArrayList<>();
        
        for (LabTest test : allTests) {
            String name = test.getName().toLowerCase();
            if (name.contains("x-ray")) {
                xrays.add(test);
            } else if (name.contains("ultrasound")) {
                ultrasounds.add(test);
            } else {
                diagnostics.add(test);
            }
        }

        diagnosticsAdapter = new LabTestAdapter(diagnostics, this::onTestSelected);
        xrayAdapter = new LabTestAdapter(xrays, this::onTestSelected);
        ultrasoundAdapter = new LabTestAdapter(ultrasounds, this::onTestSelected);

        rvDiagnostics.setAdapter(diagnosticsAdapter);
        rvXray.setAdapter(xrayAdapter);
        rvUltrasound.setAdapter(ultrasoundAdapter);
    }

    private void populateSummary() {
        TextView tvTest = findViewById(R.id.tvConfirmTest);
        TextView tvCategory = findViewById(R.id.tvConfirmCategory);
        TextView tvDoctor = findViewById(R.id.tvConfirmDoctor);
        TextView tvDate = findViewById(R.id.tvConfirmDate);
        TextView tvTime = findViewById(R.id.tvConfirmTime);
        TextView tvFee = findViewById(R.id.tvConfirmFee);
        TextView tvInstructions = findViewById(R.id.tvConfirmInstructions);

        tvTest.setText(selectedTest.getName());
        String name = selectedTest.getName().toLowerCase();
        tvCategory.setText(name.contains("x-ray") ? "X-Ray" : name.contains("ultrasound") ? "Ultrasound" : "Clinical Diagnostics");
        tvDoctor.setText(selectedDoctor.getName());
        
        String dateStr = String.format(Locale.getDefault(), "%tA, %tB %td, %tY", selectedDate, selectedDate, selectedDate, selectedDate);
        tvDate.setText(dateStr);
        tvTime.setText(selectedTime);
        tvFee.setText(String.format(Locale.getDefault(), "₱%.0f", selectedTest.getPrice()));
        tvInstructions.setText(selectedTest.getInstructions());
    }

    private void bookAppointment() {
        btnNext.setEnabled(false);
        btnNext.setText("Processing...");

        String dateStr = String.format(Locale.getDefault(), "%02d/%02d/%d", 
                selectedDate.get(Calendar.MONTH) + 1, 
                selectedDate.get(Calendar.DAY_OF_MONTH), 
                selectedDate.get(Calendar.YEAR));
                
        String fullDateStr = String.format(Locale.getDefault(), "%tA, %tB %td", selectedDate, selectedDate, selectedDate);

        SharedPreferences prefs = getSharedPreferences("UserPrefs", MODE_PRIVATE);
        int userId = prefs.getInt("userId", 1); // Default to 1 if not found

        Appointment newAppt = new Appointment(userId, selectedTest.getId(), selectedTest.getName(), dateStr, selectedTime, Integer.parseInt(selectedDoctor.getId()));

        com.example.labconnect.api.ApiClient.getApiService().bookAppointment(newAppt).enqueue(new retrofit2.Callback<Appointment>() {
            @Override
            public void onResponse(retrofit2.Call<Appointment> call, retrofit2.Response<Appointment> response) {
                if (response.isSuccessful() && response.body() != null) {
                    // Notification is now saved to PostgreSQL by the PHP API automatically.
                    Toast.makeText(BookAppointmentActivity.this, "Appointment Booked Successfully", Toast.LENGTH_SHORT).show();
                    currentStep = 3;
                    viewFlipper.setDisplayedChild(currentStep);
                    updateStepUI();
                    updateStepUI();
                } else {
                    btnNext.setEnabled(true);
                    btnNext.setText("Confirm booking");
                    Toast.makeText(BookAppointmentActivity.this, "Booking failed", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(retrofit2.Call<Appointment> call, Throwable t) {
                btnNext.setEnabled(true);
                btnNext.setText("Confirm booking");
                Toast.makeText(BookAppointmentActivity.this, "Network error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
