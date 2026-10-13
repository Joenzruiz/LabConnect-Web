package com.example.labconnect.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.models.LabTest;
import com.google.android.material.card.MaterialCardView;
import java.util.List;
import java.util.Locale;

public class LabTestAdapter extends RecyclerView.Adapter<LabTestAdapter.ViewHolder> {

    private List<LabTest> tests;
    private OnTestClickListener listener;
    private LabTest selectedTest;

    public interface OnTestClickListener {
        void onTestClick(LabTest test);
    }

    public LabTestAdapter(List<LabTest> tests, OnTestClickListener listener) {
        this.tests = tests;
        this.listener = listener;
    }

    public void setSelectedTest(LabTest test) {
        this.selectedTest = test;
        notifyDataSetChanged();
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_test, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        LabTest test = tests.get(position);
        holder.tvTestName.setText(test.getName());
        holder.tvPrice.setText(String.format(Locale.getDefault(), "₱%.0f", test.getPrice()));
        
        // Selection indicator logic
        boolean isSelected = selectedTest != null && selectedTest.getId() == test.getId();
        if (isSelected) {
            holder.cardView.setStrokeColor(holder.itemView.getContext().getResources().getColor(R.color.colorPrimary));
            holder.cardView.setStrokeWidth(6);
            holder.ivCheck.setVisibility(View.VISIBLE);
            holder.cardView.setCardElevation(4);
        } else {
            holder.cardView.setStrokeWidth(0);
            holder.ivCheck.setVisibility(View.GONE);
            holder.cardView.setCardElevation(0);
        }

        holder.tvTurnaround.setText("Result: Same day");
        holder.itemView.setOnClickListener(v -> {
            setSelectedTest(test);
            if (listener != null) {
                listener.onTestClick(test);
            }
        });
    }

    @Override
    public int getItemCount() {
        return tests.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTestName, tvPrice, tvTurnaround;
        MaterialCardView cardView;
        ImageView ivCheck;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            cardView = itemView.findViewById(R.id.cardTest);
            tvTestName = itemView.findViewById(R.id.tvTestName);
            tvPrice = itemView.findViewById(R.id.tvPrice);
            tvTurnaround = itemView.findViewById(R.id.tvTurnaround);
            ivCheck = itemView.findViewById(R.id.ivCheck);
        }
    }
}
