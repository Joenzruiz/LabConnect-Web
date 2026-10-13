package com.example.labconnect.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.google.android.material.card.MaterialCardView;
import java.util.Calendar;
import java.util.List;

public class DateAdapter extends RecyclerView.Adapter<DateAdapter.ViewHolder> {

    private List<Calendar> dates;
    private OnDateClickListener listener;
    private Calendar selectedDate;

    public interface OnDateClickListener {
        void onDateClick(Calendar date);
    }

    public DateAdapter(List<Calendar> dates, OnDateClickListener listener) {
        this.dates = dates;
        this.listener = listener;
    }

    public void setSelectedDate(Calendar date) {
        this.selectedDate = date;
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_date, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        Calendar date = dates.get(position);
        
        String[] days = {"SUN", "MON", "TUE", "WED", "THU", "FRI", "SAT"};
        holder.tvDayName.setText(days[date.get(Calendar.DAY_OF_WEEK) - 1]);
        holder.tvDayNumber.setText(String.valueOf(date.get(Calendar.DAY_OF_MONTH)));

        boolean isSelected = selectedDate != null && 
                selectedDate.get(Calendar.YEAR) == date.get(Calendar.YEAR) &&
                selectedDate.get(Calendar.DAY_OF_YEAR) == date.get(Calendar.DAY_OF_YEAR);

        if (isSelected) {
            holder.cardView.setStrokeColor(androidx.core.content.ContextCompat.getColor(holder.itemView.getContext(), R.color.colorPrimary));
            holder.cardView.setStrokeWidth(4);
            holder.cardView.setCardBackgroundColor(0xFFF0FDFA);
            holder.tvDayNumber.setTextColor(androidx.core.content.ContextCompat.getColor(holder.itemView.getContext(), R.color.colorPrimary));
        } else {
            holder.cardView.setStrokeWidth(0);
            holder.cardView.setCardBackgroundColor(0xFFFFFFFF);
            holder.tvDayNumber.setTextColor(0xFF1A202C);
        }

        holder.itemView.setOnClickListener(v -> {
            setSelectedDate(date);
            listener.onDateClick(date);
        });
    }

    @Override
    public int getItemCount() {
        return dates.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvDayName, tvDayNumber;
        MaterialCardView cardView;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            cardView = itemView.findViewById(R.id.cardDate);
            tvDayName = itemView.findViewById(R.id.tvDayName);
            tvDayNumber = itemView.findViewById(R.id.tvDayNumber);
        }
    }
}
