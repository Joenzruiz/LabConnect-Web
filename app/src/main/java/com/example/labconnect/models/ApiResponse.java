package com.example.labconnect.models;

import com.google.gson.annotations.SerializedName;

public class ApiResponse {
    @SerializedName("success")
    private boolean success;

    @SerializedName("message")
    private String message;

    @SerializedName("user")
    private User user;

    // OTP response fields
    @SerializedName("to_name")
    private String toName;

    @SerializedName("to_email")
    private String toEmail;

    @SerializedName("otp_code")
    private String otpCode;

    @SerializedName("type")
    private String type;

    public boolean isSuccess() { return success; }
    public String getMessage() { return message; }
    public User getUser() { return user; }
    public String getToName() { return toName; }
    public String getToEmail() { return toEmail; }
    public String getOtpCode() { return otpCode; }
    public String getType() { return type; }
}
