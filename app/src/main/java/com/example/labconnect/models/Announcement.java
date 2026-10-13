package com.example.labconnect.models;

import com.google.gson.annotations.SerializedName;

public class Announcement {

    @SerializedName("title")
    private String title;

    @SerializedName("description")
    private String description;

    @SerializedName("date")
    private String date;

    public Announcement(String title, String description, String date) {
        this.title = title;
        this.description = description;
        this.date = date;
    }

    public String getTitle() { return title; }
    public String getDescription() { return description; }
    public String getDate() { return date; }
}
