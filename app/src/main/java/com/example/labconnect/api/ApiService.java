package com.example.labconnect.api;

import com.example.labconnect.models.Announcement;
import com.example.labconnect.models.Appointment;
import com.example.labconnect.models.AppNotification;
import com.example.labconnect.models.ApiResponse;
import com.example.labconnect.models.LabResult;
import com.example.labconnect.models.LabTest;
import com.example.labconnect.models.User;

import java.util.List;

import retrofit2.Call;
import retrofit2.http.Body;
import retrofit2.http.GET;
import retrofit2.http.POST;
import retrofit2.http.Query;

public interface ApiService {

    @retrofit2.http.FormUrlEncoded
    @POST("api/mobile_login.php")
    Call<ApiResponse> login(
            @retrofit2.http.Field("email") String email,
            @retrofit2.http.Field("password") String password
    );

    @POST("api/delete_notification.php")
    Call<ApiResponse> deleteNotification(
            @Body java.util.Map<String, Integer> body
    );

    @POST("api/register.php")
    Call<ApiResponse> register(
            @Body User user
    );

    @GET("api/tests.php")
    Call<List<LabTest>> getTests();

    @GET("api/doctors.php")
    Call<List<com.example.labconnect.models.Doctor>> getDoctors();

    @GET("api/available_schedule.php")
    Call<java.util.Map<String, List<String>>> getAvailableSchedule(
            @Query("doctor_id") String doctorId
    );

    @POST("api/appointments.php")
    Call<Appointment> bookAppointment(
            @Body Appointment appointment
    );

    @GET("api/appointments.php")
    Call<List<Appointment>> getAppointments(
            @Query("patient_id") int patientId
    );

    @POST("api/cancel_appointment.php")
    Call<ApiResponse> cancelAppointment(
            @Body java.util.Map<String, Integer> body
    );

    @GET("api/results.php")
    Call<List<LabResult>> getResults(
            @Query("patient_id") int patientId
    );

    @GET("api/notifications.php")
    Call<List<AppNotification>> getNotifications(
            @Query("patient_id") int patientId
    );

    @POST("api/notifications.php")
    Call<ApiResponse> markNotificationsRead(
            @Body java.util.Map<String, Integer> body
    );

    @GET("api/notification_count.php")
    Call<com.example.labconnect.models.UnreadCountResponse> getUnreadNotificationCount(
            @Query("patient_id") int patientId
    );

    @GET("api/announcements.php")
    Call<List<Announcement>> getAnnouncements();

    @GET("api/profile.php")
    Call<User> getProfile(
            @Query("patient_id") int patientId
    );

    @POST("api/send_otp.php")
    Call<ApiResponse> sendOtp(
            @Body java.util.Map<String, String> body
    );

    @POST("api/verify_otp.php")
    Call<ApiResponse> verifyOtp(
            @Body java.util.Map<String, String> body
    );

    @POST("api/verify_pin.php")
    Call<ApiResponse> verifyPin(
            @Body java.util.Map<String, Object> body
    );
}