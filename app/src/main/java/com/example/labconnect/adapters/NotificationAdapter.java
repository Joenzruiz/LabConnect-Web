package com.example.labconnect.adapters;

import android.app.AlertDialog;
import android.content.Context;
import android.content.SharedPreferences;
import android.graphics.Typeface;
import android.view.LayoutInflater;
import android.view.View;
import android.view.ViewGroup;
import android.widget.ImageButton;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;
import androidx.annotation.NonNull;
import androidx.recyclerview.widget.RecyclerView;
import com.example.labconnect.R;
import com.example.labconnect.api.ApiClient;
import com.example.labconnect.models.AppNotification;
import com.example.labconnect.models.ApiResponse;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class NotificationAdapter extends RecyclerView.Adapter<NotificationAdapter.ViewHolder> {

    private List<AppNotification> notifications;

    public NotificationAdapter(List<AppNotification> notifications) {
        this.notifications = notifications;
    }

    @NonNull
    @Override
    public ViewHolder onCreateViewHolder(@NonNull ViewGroup parent, int viewType) {
        View view = LayoutInflater.from(parent.getContext())
                .inflate(R.layout.item_notification, parent, false);
        return new ViewHolder(view);
    }

    @Override
    public void onBindViewHolder(@NonNull ViewHolder holder, int position) {
        AppNotification item = notifications.get(position);

        holder.tvTitle.setText(item.getTitle());
        holder.tvMessage.setText(item.getMessage());
        holder.tvDate.setText(item.getDate());

        // Bold for unread
        if (!item.isRead()) {
            holder.tvTitle.setTypeface(null, Typeface.BOLD);
            holder.itemView.setAlpha(1.0f);
        } else {
            holder.tvTitle.setTypeface(null, Typeface.NORMAL);
            holder.itemView.setAlpha(0.65f);
        }

        // Icon based on type
        String type = item.getType();
        if ("result".equals(type)) {
            holder.ivIcon.setImageResource(R.drawable.ic_results);
        } else if ("appointment".equals(type)) {
            holder.ivIcon.setImageResource(R.drawable.ic_calendar);
        } else {
            holder.ivIcon.setImageResource(R.drawable.ic_notifications);
        }

        // ── Delete button ──
        holder.btnDelete.setOnClickListener(v -> {
            Context ctx = v.getContext();

            new AlertDialog.Builder(ctx)
                .setTitle("Delete Notification")
                .setMessage("Remove this notification?")
                .setPositiveButton("Delete", (dialog, which) -> {
                    int adapterPos = holder.getAdapterPosition();
                    if (adapterPos == RecyclerView.NO_ID) return;

                    // Remove from list immediately for instant UI feedback
                    notifications.remove(adapterPos);
                    notifyItemRemoved(adapterPos);

                    // Call the API
                    SharedPreferences prefs = ctx.getSharedPreferences("UserPrefs", Context.MODE_PRIVATE);
                    int patientId = prefs.getInt("userId", 0);

                    Map<String, Integer> body = new HashMap<>();
                    body.put("notification_id", item.getId());
                    body.put("patient_id", patientId);

                    ApiClient.getApiService().deleteNotification(body)
                        .enqueue(new Callback<ApiResponse>() {
                            @Override
                            public void onResponse(Call<ApiResponse> call, Response<ApiResponse> response) {
                                // Already removed from UI — nothing else needed
                            }
                            @Override
                            public void onFailure(Call<ApiResponse> call, Throwable t) {
                                Toast.makeText(ctx, "Could not delete. Check connection.", Toast.LENGTH_SHORT).show();
                            }
                        });
                })
                .setNegativeButton("Cancel", null)
                .show();
        });
    }

    @Override
    public int getItemCount() {
        return notifications.size();
    }

    public static class ViewHolder extends RecyclerView.ViewHolder {
        TextView tvTitle, tvMessage, tvDate;
        ImageView ivIcon;
        ImageButton btnDelete;

        public ViewHolder(@NonNull View itemView) {
            super(itemView);
            tvTitle   = itemView.findViewById(R.id.tvTitle);
            tvMessage = itemView.findViewById(R.id.tvMessage);
            tvDate    = itemView.findViewById(R.id.tvDate);
            ivIcon    = itemView.findViewById(R.id.ivIcon);
            btnDelete = itemView.findViewById(R.id.btnDelete);
        }
    }
}
