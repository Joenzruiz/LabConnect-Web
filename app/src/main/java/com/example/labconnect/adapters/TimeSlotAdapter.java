package com.example.labconnect.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.google.android.material.card.MaterialCardView;
import java.util.List;

public class TimeSlotAdapter extends RecyclerView.Adapter<TimeSlotAdapter.ViewHolder> {

    private List<String> timeSlots;
    private OnTimeSlotClickListener listener;
    private String selectedTime;

    public interface OnTimeSlotClickListener {
        void onTimeSlotClick(String time);
    }

    public TimeSlotAdapter(List<String> timeSlots, OnTimeSlotClickListener listener) {
        this.timeSlots = timeSlots;
        this.listener = listener;
    }

    public void setSelectedTime(String time) {
        this.selectedTime = time;
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_time_slot, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        String time = timeSlots.get(position);
        holder.tvTimeSlot.setText(time);

        boolean isSelected = selectedTime != null && selectedTime.equals(time);
        if (isSelected) {
            holder.cardView.setStrokeColor(androidx.core.content.ContextCompat.getColor(holder.itemView.getContext(), R.color.colorPrimary));
            holder.cardView.setStrokeWidth(4);
            holder.cardView.setCardBackgroundColor(0xFFF0FDFA);
            holder.tvTimeSlot.setTextColor(androidx.core.content.ContextCompat.getColor(holder.itemView.getContext(), R.color.colorPrimary));
        } else {
            holder.cardView.setStrokeWidth(2);
            holder.cardView.setStrokeColor(0xFFE2E8F0);
            holder.cardView.setCardBackgroundColor(0xFFFFFFFF);
            holder.tvTimeSlot.setTextColor(0xFF1A202C);
        }

        holder.itemView.setOnClickListener(v -> {
            setSelectedTime(time);
            listener.onTimeSlotClick(time);
        });
    }

    @Override
    public int getItemCount() {
        return timeSlots.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTimeSlot;
        MaterialCardView cardView;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            cardView = itemView.findViewById(R.id.cardTime);
            tvTimeSlot = itemView.findViewById(R.id.tvTimeSlot);
        }
    }
}
