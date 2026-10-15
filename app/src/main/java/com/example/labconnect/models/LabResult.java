package com.example.labconnect.models;

import com.google.gson.annotations.SerializedName;

public class LabResult {
    @SerializedName("id")
    private int id;
    
    @SerializedName("appointment_id")
    private int appointmentId;
    
    @SerializedName("test_name")
    private String testName;
    
    @SerializedName("date")
    private String date;
    
    @SerializedName("pdf_url")
    private String pdfUrl;
    
    @SerializedName("status")
    private String status;

    public LabResult() {}

    public LabResult(int id, int appointmentId, String testName, String date, String status) {
        this.id = id;
        this.appointmentId = appointmentId;
        this.testName = testName;
        this.date = date;
        this.status = status;
    }

    @SerializedName("doctor_name")
    private String doctorName;

    public int getId() { return id; }
    public String getTestName() { return testName; }
    public String getDate() { return date; }
    public String getPdfUrl() { return pdfUrl; }
    public String getDoctorName() { return doctorName; }
}
