package com.example.labconnect.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.models.Appointment;
import java.util.List;

public class AppointmentAdapter extends RecyclerView.Adapter<AppointmentAdapter.ViewHolder> {

    private List<Appointment> appointments;

    public interface OnCancelClickListener {
        void onCancelClick(Appointment appointment, int position);
    }

    private OnCancelClickListener cancelClickListener;

    public AppointmentAdapter(List<Appointment> appointments, OnCancelClickListener cancelClickListener) {
        this.appointments = appointments;
        this.cancelClickListener = cancelClickListener;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_appointment, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Appointment appointment = appointments.get(position);
        holder.tvTestName.setText(appointment.getTestName());
        holder.tvDateTime.setText("Date: " + appointment.getDate() + " | Time: " + appointment.getTime());
        holder.tvStatus.setText(appointment.getStatus());
        // Use real doctor name from API
        holder.tvDoctor.setText("Doctor: " + appointment.getDoctorName());

        // Dynamic status styling
        String status = appointment.getStatus();
        if ("Confirmed".equalsIgnoreCase(status)) {
            holder.tvStatus.setBackgroundColor(0xFFE0F2F1);
            holder.tvStatus.setTextColor(0xFF004D40);
        } else if ("Completed".equalsIgnoreCase(status)) {
            holder.tvStatus.setBackgroundColor(0xFFE8F5E9);
            holder.tvStatus.setTextColor(0xFF2E7D32);
        } else if ("Cancelled".equalsIgnoreCase(status)) {
            holder.tvStatus.setBackgroundColor(0xFFFFEBEE);
            holder.tvStatus.setTextColor(0xFFC62828);
        } else {
            // Pending
            holder.tvStatus.setBackgroundColor(0xFFFFF3E0);
            holder.tvStatus.setTextColor(0xFFE65100);
        }

        // Show cancel button only for cancellable statuses
        if ("Cancelled".equalsIgnoreCase(status) || "Completed".equalsIgnoreCase(status)) {
            holder.btnCancelAppt.setVisibility(View.GONE);
        } else {
            holder.btnCancelAppt.setVisibility(View.VISIBLE);
            holder.btnCancelAppt.setOnClickListener(v -> {
                if (cancelClickListener != null) {
                    cancelClickListener.onCancelClick(appointment, holder.getAdapterPosition());
                }
            });
        }
    }

    @Override
    public int getItemCount() {
        return appointments.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTestName, tvDateTime, tvStatus, tvDoctor, btnCancelAppt;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTestName = itemView.findViewById(R.id.tvTestName);
            tvDateTime = itemView.findViewById(R.id.tvDateTime);
            tvStatus = itemView.findViewById(R.id.tvStatus);
            tvDoctor = itemView.findViewById(R.id.tvDoctor);
            btnCancelAppt = itemView.findViewById(R.id.btnCancelAppt);
        }
    }
}
