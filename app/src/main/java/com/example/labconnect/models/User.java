package com.example.labconnect.models;

import com.google.gson.annotations.SerializedName;

public class User {

    @SerializedName("id")
    private int id;

    @SerializedName("full_name")
    private String fullName;

    @SerializedName("email")
    private String email;

    @SerializedName("mobile")
    private String mobile;

    @SerializedName("dob")
    private String dob;

    @SerializedName("gender")
    private String gender;

    @SerializedName("address")
    private String address;

    @SerializedName("password")
    private String password;

    @SerializedName("confirm_password")
    private String confirmPassword;

    @SerializedName("result_pin")
    private String resultPin;

    public User(
            String fullName,
            String email,
            String mobile,
            String dob,
            String gender,
            String address,
            String password,
            String confirmPassword,
            String resultPin
    ) {
        this.fullName = fullName;
        this.email = email;
        this.mobile = mobile;
        this.dob = dob;
        this.gender = gender;
        this.address = address;
        this.password = password;
        this.confirmPassword = confirmPassword;
        this.resultPin = resultPin;
    }

    public User(String email, String password) {
        this.email = email;
        this.password = password;
    }

    public int getId() {
        return id;
    }

    public String getFullName() {
        return fullName;
    }

    public String getEmail() {
        return email;
    }

    public String getMobile() {
        return mobile;
    }

    public String getDob() {
        return dob;
    }

    public String getGender() {
        return gender;
    }

    public String getAddress() {
        return address;
    }

    public String getPassword() {
        return password;
    }

    public String getConfirmPassword() {
        return confirmPassword;
    }

    public String getResultPin() {
        return resultPin;
    }
}