import React from "react";
import { useStatistics } from "../hooks/useStatistics";

const Statistics = () => {
  const { statistics, isLoading, error } = useStatistics();

  if (isLoading) {
    return (
      <div className="statistics-section">
        <div className="loading">Loading statistics...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="statistics-section">
        <div className="error-message">
          Error loading statistics: {error.message || 'Unknown error'}
        </div>
      </div>
    );
  }

  if (!statistics) {
    return null;
  }

  const { employees, meal_choices } = statistics;

  return (
    <div className="statistics-section">
      <h2>📊 Statistics</h2>
      <div className="stats-grid">
        <div className="stat-card">
          <div className="stat-label">Total Employees</div>
          <div className="stat-value">{employees.total}</div>
        </div>
        <div className="stat-card stat-success">
          <div className="stat-label">With Slack ID</div>
          <div className="stat-value">{employees.with_slack_id}</div>
        </div>
        <div className={`stat-card ${employees.without_slack_id > 0 ? 'stat-warning' : 'stat-success'}`}>
          <div className="stat-label">Without Slack ID</div>
          <div className="stat-value">{employees.without_slack_id}</div>
        </div>
        <div className="stat-card">
          <div className="stat-label">Total Meal Choices</div>
          <div className="stat-value">{meal_choices.total}</div>
        </div>
        <div className="stat-card stat-success">
          <div className="stat-label">Choices with Slack ID</div>
          <div className="stat-value">{meal_choices.with_slack_id}</div>
        </div>
        <div className={`stat-card ${meal_choices.without_slack_id > 0 ? 'stat-warning' : 'stat-success'}`}>
          <div className="stat-label">Choices without Slack ID</div>
          <div className="stat-value">{meal_choices.without_slack_id}</div>
        </div>
      </div>
      {employees.without_slack_id > 0 && (
        <div className="statistics-warning">
          <strong>⚠️ Warning:</strong> {employees.without_slack_id} employee(s) don't have a Slack ID set. 
          Upload a CSV file with the <code>slack_id</code> column to enable Slack notifications.
          <br />
          <small style={{ marginTop: '8px', display: 'block' }}>
            <strong>Slack ID format:</strong> Use the ID from Slack URLs (e.g., <code>D0A5BA1001X</code> from <code>https://energybeam.slack.com/archives/D0A5BA1001X</code>)
            <br />
            For channels: Use the channel ID (starts with C or D)
            <br />
            For users: Use the user ID (starts with U)
          </small>
        </div>
      )}
    </div>
  );
};

export default Statistics;

