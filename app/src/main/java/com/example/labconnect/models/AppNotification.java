package com.example.labconnect.models;

import com.google.gson.annotations.SerializedName;

public class AppNotification {

    @SerializedName("id")
    private int id;

    @SerializedName("title")
    private String title;

    @SerializedName("message")
    private String message;

    @SerializedName("date")
    private String date;

    @SerializedName("type")
    private String type;

    @SerializedName("is_read")
    private boolean isRead;

    public AppNotification(String title, String message, String date, String type) {
        this.title = title;
        this.message = message;
        this.date = date;
        this.type = type;
    }

    public int getId() { return id; }
    public String getTitle() { return title; }
    public String getMessage() { return message; }
    public String getDate() { return date; }
    public String getType() { return type; }
    public boolean isRead() { return isRead; }
}
