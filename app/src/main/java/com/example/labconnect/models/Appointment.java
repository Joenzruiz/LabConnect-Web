package com.example.labconnect.models;

import com.google.gson.annotations.SerializedName;

public class Appointment {
    @SerializedName("id")
    private int id;
    
    @SerializedName("patient_id")
    private int patientId;
    
    @SerializedName("test_id")
    private int testId;
    
    @SerializedName("test_name")
    private String testName;
    
    @SerializedName("date")
    private String date;
    
    @SerializedName("time")
    private String time;
    
    @SerializedName("status")
    private String status; // Pending, Approved, Cancelled

    @SerializedName("doctor_name")
    private String doctorName;

    @SerializedName("doctor_id")
    private int doctorId;

    public Appointment(int patientId, int testId, String testName, String date, String time, int doctorId) {
        this.patientId = patientId;
        this.testId = testId;
        this.testName = testName;
        this.date = date;
        this.time = time;
        this.doctorId = doctorId;
        this.status = "Pending";
    }

    // Getters and Setters
    public int getId() { return id; }
    public String getTestName() { return testName; }
    public String getDate() { return date; }
    public String getTime() { return time; }
    public String getStatus() { return status; }
    public String getDoctorName() { return doctorName != null && !doctorName.isEmpty() ? doctorName : "SLIMM Laboratory"; }
    public void setStatus(String status) { this.status = status; }
}
