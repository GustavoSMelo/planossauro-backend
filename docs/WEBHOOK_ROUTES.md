# Webhook Routes

Base URL: `http://localhost:8080/api`

---

## POST /webhook/payment

Stripe webhook handler for payment events.

### Route Details

| Attribute | Value |
|-----------|-------|
| **Method** | POST |
| **Path** | `/webhook/payment` |
| **Full URL** | `http://localhost:8080/api/webhook/payment` |
| **Authentication** | None (Stripe signature verification) |
| **Middleware** | None |
| **Rate Limit** | Default |

### Headers

| Header | Required | Description |
|--------|----------|-------------|
| `Stripe-Signature` | Yes | Stripe webhook signature for verification |
| `Content-Type` | Yes | Must be `application/json` |

### Request Body

The request body contains the raw Stripe webhook event. Example event types:

| Event Type | Description |
|------------|-------------|
| `checkout.session.completed` | Checkout session completed |
| `payment_intent.succeeded` | Payment successful |
| `payment_intent.payment_failed` | Payment failed |
| `invoice.paid` | Invoice paid |
| `invoice.payment_failed` | Invoice payment failed |
| `customer.subscription.created` | Subscription created |
| `customer.subscription.updated` | Subscription updated |
| `customer.subscription.deleted` | Subscription deleted |

### Example Webhook Payload

```json
{
  "id": "evt_1234567890",
  "type": "payment_intent.succeeded",
  "data": {
    "object": {
      "id": "pi_1234567890",
      "amount": 4990,
      "currency": "brl",
      "status": "succeeded",
      "metadata": {
        "user_uuid": "550e8400-e29b-41d4-a716-446655440000",
        "subscription_uuid": "990e8400-e29b-41d4-a716-446655440005"
      }
    }
  }
}
```

### Response

**Success (200 OK)**

```json
{
  "received": true
}
```

**Invalid Signature (400 Bad Request)**

```json
{
  "error": "Invalid signature"
}
```

### Curl Example

```bash
curl -X POST "http://localhost:8080/api/webhook/payment" \
  -H "Content-Type: application/json" \
  -H "Stripe-Signature: {stripe_signature}" \
  -d '{
    "id": "evt_1234567890",
    "type": "payment_intent.succeeded",
    "data": {
      "object": {
        "id": "pi_1234567890",
        "amount": 4990,
        "currency": "brl",
        "status": "succeeded"
      }
    }
  }'
```

### Testing Webhooks Locally

Use Stripe CLI to forward webhooks to your local environment:

```bash
stripe listen --forward-to localhost:8080/api/webhook/payment
```

### Security Notes

- The endpoint verifies Stripe signatures to ensure events are authentic
- Never expose or log the raw request body in production
- Ensure your webhook endpoint responds quickly (within 30 seconds)
- Return 200 status code to prevent Stripe from retrying
