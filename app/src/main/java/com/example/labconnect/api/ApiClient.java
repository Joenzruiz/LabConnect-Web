package com.example.labconnect.api;

import retrofit2.Retrofit;
import retrofit2.converter.gson.GsonConverterFactory;

public class ApiClient {

    public static final String BASE_URL = "http://192.168.1.37/LabConnect-Web/";

    // Do NOT cache — always create fresh so IP changes take effect
    public static ApiService getApiService() {
        return new Retrofit.Builder()
                .baseUrl(BASE_URL)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(ApiService.class);
    }
}