package com.example.labconnect.models;

import com.google.gson.annotations.SerializedName;

public class LabTest {
    @SerializedName("id")
    private int id;
    
    @SerializedName("name")
    private String name;
    
    @SerializedName("price")
    private double price;
    
    @SerializedName("instructions")
    private String instructions;

    public LabTest(int id, String name, double price, String instructions) {
        this.id = id;
        this.name = name;
        this.price = price;
        this.instructions = instructions;
    }

    // Getters
    public int getId() { return id; }
    public String getName() { return name; }
    public double getPrice() { return price; }
    public String getInstructions() { return instructions; }
}
