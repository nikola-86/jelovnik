import React from "react";
import { useMealChoices } from "../hooks/useMealChoices";

const MealChoicesTable = () => {
  const { mealChoices, isLoading, error } = useMealChoices();

  if (isLoading) {
    return (
      <div className="table-section">
        <h2>Meal Choices</h2>
        <div className="loading">Loading meal choices</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="table-section">
        <h2>Meal Choices</h2>
        <div className="error-message">
          Error loading meal choices: {error.message || 'Unknown error'}
        </div>
      </div>
    );
  }

  const getStatusClass = (status) => {
    const statusLower = (status || 'pending').toLowerCase();
    if (statusLower === 'sent' || statusLower === 'success') return 'sent';
    if (statusLower === 'failed' || statusLower === 'error') return 'failed';
    return 'pending';
  };

  return (
    <div className="table-section">
      <h2>Meal Choices</h2>
      {mealChoices.length === 0 ? (
        <div className="empty-state">
          <div className="empty-state-icon">📋</div>
          <div className="empty-state-text">No meal choices found. Upload a file to get started!</div>
        </div>
      ) : (
        <div className="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>Employee Name</th>
                <th>Meal Choice</th>
                <th>Date</th>
                <th>Slack Status</th>
              </tr>
            </thead>
            <tbody>
              {mealChoices.map((item) => {
                const hasSlackId = item.employee.slack_id && item.employee.slack_id.trim() !== '';
                return (
                  <tr key={item.id} className={!hasSlackId ? 'no-slack-id' : ''}>
                    <td>
                      <strong>{item.employee.name}</strong>
                      <br />
                      <small style={{ color: '#718096', fontSize: '12px' }}>
                        {item.employee.email}
                      </small>
                      {!hasSlackId && (
                        <>
                          <br />
                          <small style={{ color: '#ed8936', fontSize: '11px', fontWeight: '600' }}>
                            ⚠️ No Slack ID
                          </small>
                        </>
                      )}
                    </td>
                    <td>
                      <span style={{ fontWeight: '500' }}>{item.choice}</span>
                    </td>
                    <td>{item.date}</td>
                    <td>
                      {hasSlackId ? (
                        <span className={`status-badge ${getStatusClass(item.slack_status)}`}>
                          {item.slack_status || 'pending'}
                        </span>
                      ) : (
                        <span className="status-badge pending" style={{ opacity: 0.6 }}>
                          N/A
                        </span>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default MealChoicesTable;
