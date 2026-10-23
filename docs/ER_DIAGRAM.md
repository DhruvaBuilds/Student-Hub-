# ER Diagram – StudentHub

```mermaid
erDiagram
    USERS ||--o| STUDENTS : "has profile"
    USERS ||--o{ EVENTS : "creates"
    USERS ||--o{ AUDIT_LOGS : "performs"
    USERS ||--o{ REMEMBER_TOKENS : "owns"
    STUDENTS ||--o{ REGISTRATIONS : "makes"
    EVENTS ||--o{ REGISTRATIONS : "receives"

    USERS {
        int id PK
        varchar name
        varchar email UK
        varchar password_hash
        enum role "admin|student"
        enum status "active|inactive"
        datetime last_login
        timestamp created_at
    }
    STUDENTS {
        int id PK
        int user_id FK "nullable, unique"
        varchar student_code UK
        varchar name
        varchar email UK
        varchar phone
        varchar course
        tinyint year
        enum gender
        enum status "active|inactive|deleted"
    }
    EVENTS {
        int id PK
        varchar title
        text description
        date event_date
        varchar location
        varchar category
        enum status "open|closed"
        varchar poster
        int created_by FK
    }
    REGISTRATIONS {
        int id PK
        int student_id FK
        int event_id FK
        datetime registered_at
    }
    AUDIT_LOGS {
        bigint id PK
        int user_id FK
        varchar action
        varchar details
        varchar ip_address
        timestamp created_at
    }
    REMEMBER_TOKENS {
        int id PK
        int user_id FK
        char selector UK
        char token_hash "SHA-256 of the cookie validator"
        datetime expires_at
    }
```

**Normalisation:** every table is in 3NF – a student's login data lives in `users`, academic data in `students`,
and the many-to-many relation between students and events is resolved by `registrations`
(with a unique key on `student_id + event_id`).

**Keys / rules:** `registrations` rows are removed automatically when a student or event is deleted (`ON DELETE CASCADE`);
`audit_logs.user_id` and `events.created_by` become `NULL` if a user is removed (`ON DELETE SET NULL`) so history is kept.
