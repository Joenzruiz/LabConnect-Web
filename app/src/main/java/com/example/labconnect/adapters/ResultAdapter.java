package com.example.labconnect.adapters;

import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageView;
import android.widget.TextView;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.models.LabResult;
import java.util.List;

public class ResultAdapter extends RecyclerView.Adapter<ResultAdapter.ViewHolder> {

    private List<LabResult> results;
    private OnResultClickListener listener;

    public interface OnResultClickListener {
        void onResultClick(LabResult result);
    }

    public ResultAdapter(List<LabResult> results, OnResultClickListener listener) {
        this.results = results;
        this.listener = listener;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext()).inflate(R.layout.item_result, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        LabResult result = results.get(position);
        holder.tvTestName.setText(result.getTestName());
        holder.tvDate.setText("Completed • " + result.getDate());
        holder.itemView.setOnClickListener(v -> listener.onResultClick(result));
    }

    @Override
    public int getItemCount() {
        return results.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTestName, tvDate;
        ImageView ivLock;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTestName = itemView.findViewById(R.id.tvTestName);
            tvDate = itemView.findViewById(R.id.tvDate);
            ivLock = itemView.findViewById(R.id.ivLock);
        }
    }
}
