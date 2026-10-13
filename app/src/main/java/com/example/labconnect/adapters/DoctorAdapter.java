package com.example.labconnect.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.models.Doctor;
import com.google.android.material.card.MaterialCardView;
import java.util.List;

public class DoctorAdapter extends RecyclerView.Adapter<DoctorAdapter.ViewHolder> {

    private List<Doctor> doctors;
    private OnDoctorClickListener listener;
    private Doctor selectedDoctor;

    public interface OnDoctorClickListener {
        void onDoctorClick(Doctor doctor);
    }

    public DoctorAdapter(List<Doctor> doctors, OnDoctorClickListener listener) {
        this.doctors = doctors;
        this.listener = listener;
    }

    public void setSelectedDoctor(Doctor doctor) {
        this.selectedDoctor = doctor;
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_doctor, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Doctor doctor = doctors.get(position);
        holder.tvName.setText(doctor.getName());
        holder.tvSpecialty.setText(doctor.getSpecialty());
        holder.tvInitials.setText(doctor.getInitials());

        boolean isSelected = selectedDoctor != null && selectedDoctor.getId().equals(doctor.getId());
        if (isSelected) {
            holder.cardView.setStrokeColor(androidx.core.content.ContextCompat.getColor(holder.itemView.getContext(), R.color.colorPrimary));
            holder.cardView.setStrokeWidth(4);
            holder.cardView.setCardBackgroundColor(0xFFF0FDFA);
        } else {
            holder.cardView.setStrokeWidth(0);
            holder.cardView.setCardBackgroundColor(0xFFFFFFFF);
        }

        holder.itemView.setOnClickListener(v -> {
            setSelectedDoctor(doctor);
            listener.onDoctorClick(doctor);
        });
    }

    @Override
    public int getItemCount() {
        return doctors.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvName, tvSpecialty, tvInitials;
        MaterialCardView cardView;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            cardView = itemView.findViewById(R.id.cardDoctor);
            tvName = itemView.findViewById(R.id.tvDoctorName);
            tvSpecialty = itemView.findViewById(R.id.tvDoctorSpecialty);
            tvInitials = itemView.findViewById(R.id.tvDoctorInitials);
        }
    }
}
