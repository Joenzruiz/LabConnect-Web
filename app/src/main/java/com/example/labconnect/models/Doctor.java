package com.example.labconnect.models;

public class Doctor {
    private String id;
    private String name;
    private String specialty;
    private String initials;

    public Doctor(String id, String name, String specialty, String initials) {
        this.id = id;
        this.name = name;
        this.specialty = specialty;
        this.initials = initials;
    }

    public String getId() { return id; }
    public String getName() { return name; }
    public String getSpecialty() { return specialty; }
    public String getInitials() { return initials; }
}
