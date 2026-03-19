# HillSide API - Endpoint Documentation

All endpoints require `Content-Type: application/json`.  
All authenticated endpoints require the header: `Authorization: Bearer <jwt_token>`

---

## Table of Contents

1. [Business Types](#1-list-business-types)
2. [Create Business](#2-create-business)
3. [Get My Business](#3-get-my-business)
4. [Update Business](#4-update-business)
5. [Assign Business Type](#5-assign-business-type)
6. [Create AI Configuration](#6-create-ai-configuration)
7. [Get AI Configuration](#7-get-ai-configuration)

---

## Typical Onboarding Flow

```
1. POST /api/auth/register         → User registers
2. GET  /api/business-types        → Frontend shows available business types
3. POST /api/businesses            → User creates their business
4. PUT  /api/businesses/1/business-type → User selects their business type
5. POST /api/businesses/1/ai-config    → User configures their AI assistant
```

---

## 1. List Business Types

Returns all supported business types that the platform supports. Used during registration so the user can pick what kind of business they are.

**Endpoint:** `GET /api/business-types`  
**Auth:** Required  

### Query Parameters

| Param      | Type   | Required | Description                                        |
|------------|--------|----------|----------------------------------------------------|
| `category` | string | No       | Filter by category. Values: `commerce`, `services` |

### Request Example

```
GET /api/business-types?category=commerce
Authorization: Bearer <token>
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "category": "commerce",
      "name": "Clothing & Fashion",
      "slug": "clothing-fashion",
      "description": "Apparel, accessories, and fashion retail businesses.",
      "icon": null,
      "is_active": true,
      "created_at": "2026-03-19T10:00:00.000000Z",
      "updated_at": "2026-03-19T10:00:00.000000Z"
    }
  ],
  "grouped": {
    "commerce": [ ... ],
    "services": [ ... ]
  }
}
```

### Logic

- Queries `business_types` table filtered by `is_active = true`.
- Optionally filters by `category` query parameter.
- Results are sorted by category, then by name.
- Returns both a flat `data` array and a `grouped` object keyed by category.

---

## 2. Create Business

Creates a new business for the authenticated user.

**Endpoint:** `POST /api/businesses`  
**Auth:** Required  

### Request Body

| Field         | Type   | Required | Validation                   |
|---------------|--------|----------|------------------------------|
| `name`        | string | **Yes**  | max: 255                     |
| `description` | string | No       | max: 2000                    |
| `phone`       | string | No       | max: 20                      |
| `email`       | string | No       | must be valid email, max: 255|
| `address`     | string | No       | max: 500                     |
| `logo`        | string | No       | max: 500                     |
| `website`     | string | No       | must be valid URL, max: 500  |
| `timezone`    | string | No       | max: 100                     |

### Request Example

```json
POST /api/businesses
Authorization: Bearer <token>

{
  "name": "My Clothing Store",
  "description": "Premium streetwear and fashion brand.",
  "phone": "+1234567890",
  "email": "contact@mystore.com",
  "website": "https://mystore.com",
  "timezone": "Europe/London"
}
```

### Success Response — `201 Created`

```json
{
  "success": true,
  "message": "Business created successfully.",
  "data": {
    "id": 1,
    "user_id": 1,
    "business_type_id": null,
    "name": "My Clothing Store",
    "description": "Premium streetwear and fashion brand.",
    "phone": "+1234567890",
    "email": "contact@mystore.com",
    "address": null,
    "logo": null,
    "website": "https://mystore.com",
    "timezone": "Europe/London",
    "created_at": "2026-03-19T10:00:00.000000Z",
    "updated_at": "2026-03-19T10:00:00.000000Z",
    "business_type": null
  }
}
```

### Error Response — `422 Unprocessable Entity`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "name": ["A business name is required."],
    "email": ["Please provide a valid email address."]
  }
}
```

### Logic

- `BusinessController@store` receives the validated data from `StoreBusinessRequest`.
- Delegates to `BusinessService::createBusiness()` which sets `user_id` from the authenticated user and creates the record.
- Returns the created business with its `businessType` relation (null until assigned).

---

## 3. Get My Business

Returns the first business belonging to the authenticated user.

**Endpoint:** `GET /api/business/me`  
**Auth:** Required  

### Request Example

```
GET /api/business/me
Authorization: Bearer <token>
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "business_type_id": 3,
    "name": "My Clothing Store",
    "description": "Premium streetwear and fashion brand.",
    "phone": "+1234567890",
    "email": "contact@mystore.com",
    "address": null,
    "logo": null,
    "website": "https://mystore.com",
    "timezone": "Europe/London",
    "created_at": "2026-03-19T10:00:00.000000Z",
    "updated_at": "2026-03-19T10:00:00.000000Z",
    "business_type": {
      "id": 3,
      "category": "commerce",
      "name": "Clothing & Fashion",
      "slug": "clothing-fashion",
      "description": "Apparel, accessories, and fashion retail businesses.",
      "icon": null,
      "is_active": true
    }
  }
}
```

### Error Response — `404 Not Found`

```json
{
  "success": false,
  "message": "No business found. Please create a business first."
}
```

### Logic

- Queries the authenticated user's first business with the `businessType` relation eager-loaded.
- Returns 404 if the user hasn't created a business yet.

---

## 4. Update Business

Updates an existing business's details. Only the owner can update.

**Endpoint:** `PUT /api/businesses/{business}`  
**Auth:** Required  

### URL Parameters

| Param      | Type    | Description             |
|------------|---------|-------------------------|
| `business` | integer | The business ID to update |

### Request Body

All fields are optional — only send what you want to change.

| Field         | Type   | Required | Validation                   |
|---------------|--------|----------|------------------------------|
| `name`        | string | No       | max: 255                     |
| `description` | string | No       | max: 2000                    |
| `phone`       | string | No       | max: 20                      |
| `email`       | string | No       | must be valid email, max: 255|
| `address`     | string | No       | max: 500                     |
| `logo`        | string | No       | max: 500                     |
| `website`     | string | No       | must be valid URL, max: 500  |
| `timezone`    | string | No       | max: 100                     |

### Request Example

```json
PUT /api/businesses/1
Authorization: Bearer <token>

{
  "name": "My Updated Store Name",
  "address": "123 Main Street, London"
}
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "message": "Business updated successfully.",
  "data": {
    "id": 1,
    "name": "My Updated Store Name",
    "address": "123 Main Street, London",
    "business_type": { ... }
  }
}
```

### Error Response — `403 Forbidden`

```json
{
  "success": false,
  "message": "You do not own this business."
}
```

### Logic

- `BusinessController@update` first checks the authenticated user owns the business (compares `user_id`).
- Delegates to `BusinessService::updateBusiness()` which applies only the validated fields and refreshes the model.
- Returns the updated business with its `businessType` relation.

---

## 5. Assign Business Type

Assigns (or changes) the business type for a business. This tells the platform what kind of business it is (commerce vs services, and the specific subcategory).

**Endpoint:** `PUT /api/businesses/{business}/business-type`  
**Auth:** Required  

### URL Parameters

| Param      | Type    | Description             |
|------------|---------|-------------------------|
| `business` | integer | The business ID          |

### Request Body

| Field              | Type    | Required | Validation                             |
|--------------------|---------|----------|----------------------------------------|
| `business_type_id` | integer | **Yes**  | must exist in `business_types` table   |

### Request Example

```json
PUT /api/businesses/1/business-type
Authorization: Bearer <token>

{
  "business_type_id": 1
}
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "message": "Business type assigned successfully.",
  "data": {
    "id": 1,
    "user_id": 1,
    "business_type_id": 1,
    "name": "My Clothing Store",
    "business_type": {
      "id": 1,
      "category": "commerce",
      "name": "Clothing & Fashion",
      "slug": "clothing-fashion",
      "description": "Apparel, accessories, and fashion retail businesses."
    }
  }
}
```

### Error Responses

**`403 Forbidden`** — Not the owner:

```json
{
  "success": false,
  "message": "You do not own this business."
}
```

**`422 Unprocessable Entity`** — Invalid business type:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "business_type_id": ["The selected business type does not exist."]
  }
}
```

### Logic

- `BusinessController@assignBusinessType` checks ownership.
- Delegates to `BusinessService::assignBusinessType()` which:
  1. Looks up the `business_type_id` and verifies it is `is_active = true`.
  2. Updates the business's `business_type_id` column.
  3. Returns the business with the `businessType` relation loaded.

---

## 6. Create AI Configuration

Creates the full AI configuration for a business. This is a single endpoint that creates three sub-records (`AiPersonality`, `AiRestriction`, `AiSalesman`) and links them via one `AiConfig` record — all inside a database transaction.

Each business can only have **one** AI configuration.

**Endpoint:** `POST /api/businesses/{business}/ai-config`  
**Auth:** Required  

### URL Parameters

| Param      | Type    | Description    |
|------------|---------|----------------|
| `business` | integer | The business ID |

### Request Body

The body is a nested JSON object with three required sections:

#### `personality` (required)

| Field                | Type   | Required | Validation                                                           |
|----------------------|--------|----------|----------------------------------------------------------------------|
| `tone`               | string | **Yes**  | One of: `professional`, `friendly`, `casual`, `formal`, `enthusiastic` |
| `response_style`     | string | **Yes**  | One of: `concise`, `balanced`, `detailed`                            |
| `language`           | string | No       | max: 10. Defaults to `en`                                            |
| `greeting_message`   | string | No       | max: 1000                                                            |
| `farewell_message`   | string | No       | max: 1000                                                            |
| `custom_instructions`| string | No       | max: 5000                                                            |

#### `restrictions` (required)

| Field                  | Type     | Required | Validation                     |
|------------------------|----------|----------|--------------------------------|
| `allowed_topics`       | string[] | No       | each item max: 255             |
| `restricted_topics`    | string[] | No       | each item max: 255             |
| `blocked_words`        | string[] | No       | each item max: 100             |
| `max_response_length`  | integer  | No       | min: 50, max: 10000            |
| `content_guidelines`   | string   | No       | max: 5000                      |

#### `salesman` (required)

| Field               | Type    | Required | Validation                                                        |
|---------------------|---------|----------|-------------------------------------------------------------------|
| `sales_approach`    | string  | **Yes**  | One of: `consultative`, `soft_sell`, `direct`, `relationship_based` |
| `upsell_enabled`    | boolean | No       | Defaults to `false`                                                |
| `product_knowledge` | string  | No       | max: 5000                                                          |
| `pricing_info`      | string  | No       | max: 5000                                                          |
| `call_to_action`    | string  | No       | max: 1000                                                          |
| `objection_handling`| string  | No       | max: 5000                                                          |

#### Root-level

| Field       | Type    | Required | Description                |
|-------------|---------|----------|----------------------------|
| `is_active` | boolean | No       | Defaults to `true`          |

### Request Example

```json
POST /api/businesses/1/ai-config
Authorization: Bearer <token>

{
  "personality": {
    "tone": "friendly",
    "response_style": "balanced",
    "language": "en",
    "greeting_message": "Hello! Welcome to our store. How can I help you today?",
    "farewell_message": "Thank you for reaching out! Have a great day.",
    "custom_instructions": "Always recommend our latest collection when relevant."
  },
  "restrictions": {
    "allowed_topics": ["products", "pricing", "shipping", "returns", "promotions"],
    "restricted_topics": ["politics", "religion", "competitors"],
    "blocked_words": ["cheap", "worst"],
    "max_response_length": 500,
    "content_guidelines": "Keep responses professional. Never make promises about delivery dates."
  },
  "salesman": {
    "sales_approach": "consultative",
    "upsell_enabled": true,
    "product_knowledge": "We sell premium streetwear. Our top products are the Classic Hoodie ($89) and the Urban Jacket ($149).",
    "pricing_info": "Free shipping over $50. 10% off first order with code WELCOME10.",
    "call_to_action": "Check out our new arrivals at mystore.com/new",
    "objection_handling": "If the customer says it's too expensive, mention our payment plans and current promotions."
  },
  "is_active": true
}
```

### Success Response — `201 Created`

```json
{
  "success": true,
  "message": "AI configuration created successfully.",
  "data": {
    "id": 1,
    "business_id": 1,
    "ai_personality_id": 1,
    "ai_restrictions_id": 1,
    "ai_salesman_id": 1,
    "is_active": true,
    "created_at": "2026-03-19T10:00:00.000000Z",
    "updated_at": "2026-03-19T10:00:00.000000Z",
    "personality": {
      "id": 1,
      "tone": "friendly",
      "response_style": "balanced",
      "language": "en",
      "greeting_message": "Hello! Welcome to our store. How can I help you today?",
      "farewell_message": "Thank you for reaching out! Have a great day.",
      "custom_instructions": "Always recommend our latest collection when relevant."
    },
    "restrictions": {
      "id": 1,
      "allowed_topics": ["products", "pricing", "shipping", "returns", "promotions"],
      "restricted_topics": ["politics", "religion", "competitors"],
      "blocked_words": ["cheap", "worst"],
      "max_response_length": 500,
      "content_guidelines": "Keep responses professional. Never make promises about delivery dates."
    },
    "salesman": {
      "id": 1,
      "sales_approach": "consultative",
      "upsell_enabled": true,
      "product_knowledge": "We sell premium streetwear...",
      "pricing_info": "Free shipping over $50...",
      "call_to_action": "Check out our new arrivals at mystore.com/new",
      "objection_handling": "If the customer says it's too expensive..."
    }
  }
}
```

### Error Responses

**`403 Forbidden`** — Not the owner:

```json
{
  "success": false,
  "message": "You do not own this business."
}
```

**`409 Conflict`** — AI config already exists:

```json
{
  "success": false,
  "message": "This business already has an AI configuration. Each business can only have one AI configuration."
}
```

**`422 Unprocessable Entity`** — Validation errors:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "personality.tone": ["Invalid AI tone. Allowed: professional, friendly, casual, formal, enthusiastic"],
    "salesman.sales_approach": ["Sales approach is required."]
  }
}
```

### Logic

- `AiConfigController@store` checks business ownership, then checks if a config already exists (returns 409 if so).
- Delegates to `AiConfigService::createConfig()` which runs inside a `DB::transaction`:
  1. Creates an `AiPersonality` record from `data.personality`.
  2. Creates an `AiRestriction` record from `data.restrictions`.
  3. Creates an `AiSalesman` record from `data.salesman`.
  4. Creates an `AiConfig` record linking all three to the business.
- If any step fails, the entire transaction rolls back — no orphan records.
- Returns the config with all three relations eager-loaded.

---

## 7. Get AI Configuration

Returns the AI configuration for a specific business, including all sub-configurations.

**Endpoint:** `GET /api/businesses/{business}/ai-config`  
**Auth:** Required  

### URL Parameters

| Param      | Type    | Description    |
|------------|---------|----------------|
| `business` | integer | The business ID |

### Request Example

```
GET /api/businesses/1/ai-config
Authorization: Bearer <token>
```

### Success Response — `200 OK`

```json
{
  "success": true,
  "data": {
    "id": 1,
    "business_id": 1,
    "ai_personality_id": 1,
    "ai_restrictions_id": 1,
    "ai_salesman_id": 1,
    "is_active": true,
    "personality": { ... },
    "restrictions": { ... },
    "salesman": { ... }
  }
}
```

### Error Responses

**`403 Forbidden`** — Not the owner:

```json
{
  "success": false,
  "message": "You do not own this business."
}
```

**`404 Not Found`** — No config exists yet:

```json
{
  "success": false,
  "message": "No AI configuration found for this business."
}
```

### Logic

- `AiConfigController@show` checks business ownership.
- Delegates to `AiConfigService::getConfigForBusiness()` which loads the config with `personality`, `restrictions`, and `salesman` relations.
- Returns 404 if no config has been created yet.

---

## Enum Reference

### AiTone (personality.tone)

| Value          | Label          |
|----------------|----------------|
| `professional` | Professional   |
| `friendly`     | Friendly       |
| `casual`       | Casual         |
| `formal`       | Formal         |
| `enthusiastic` | Enthusiastic   |

### AiResponseStyle (personality.response_style)

| Value      | Label    |
|------------|----------|
| `concise`  | Concise  |
| `balanced` | Balanced |
| `detailed` | Detailed |

### SalesApproach (salesman.sales_approach)

| Value                | Label              |
|----------------------|--------------------|
| `consultative`       | Consultative       |
| `soft_sell`          | Soft Sell          |
| `direct`             | Direct             |
| `relationship_based` | Relationship Based |

### BusinessCategory (business_types.category)

| Value      | Label    |
|------------|----------|
| `commerce` | Commerce |
| `services` | Services |
