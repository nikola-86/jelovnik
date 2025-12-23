#!/bin/bash

# Add Slack webhook URL to .env
WEBHOOK_URL="https://hooks.slack.com/services/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"

if grep -q "SLACK_WEBHOOK_URL" .env; then
    # Update existing entry
    sed -i "s|^SLACK_WEBHOOK_URL=.*|SLACK_WEBHOOK_URL=$WEBHOOK_URL|" .env
    echo "Updated SLACK_WEBHOOK_URL in .env"
else
    # Add new entry
    echo "SLACK_WEBHOOK_URL=$WEBHOOK_URL" >> .env
    echo "Added SLACK_WEBHOOK_URL to .env"
fi

# Clear Laravel config cache
echo "Clearing Laravel config cache..."
docker exec jelovnik-php php artisan config:clear

echo ""
echo "✅ Slack webhook configured!"
echo ""
echo "To test:"
echo "1. Upload a CSV file with slack_id column"
echo "2. Check your Slack channel for notifications"
echo "3. Check slack_status in the meal choices table"

