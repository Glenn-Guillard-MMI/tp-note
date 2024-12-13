# Xamp

## .env

Dans le .env de votre projet spécifier votre BDD afin que l'application fonctionne

```php
DATABASE_URL="mysql://username:password@127.0.0.1:3306/Nom_De_la_bdd"
```

Puis faite ces commande afin que l'aplication puisse crée les entity

```bash
php bin/console doctrine:database:create
php bin/console make:migration
php bin/console doctrine:migrations:migrate

```

# PostMan

Afin de pouvoir utiliser toutes les commandes disponibles, vous pouvez importer le fichier postman.json. Attention, certaines commandes ne fonctionneront pas car vous ne serez pas admin par défaut. Pensez à créer un utilisateur administrateur pour pouvoir effectuer tous les tests possibles.

# API Routes Documentation

## User Routes

### 1. Create a new user

**POST** `/api/new`

### 2. User login

**POST** `/api/login`

### 3. Update user information

**PUT** `/api/update`

### 4. Delete a user

**DELETE** `/api/delete/{email}`

### 5. Show all users

**GET** `/api/showAll`

### 6. Show a user by email

**GET** `/api/show/{email}`

---

## Reservation Routes

### 1. Create a reservation

**POST** `/api/reservation`

### 2. Add a reservation to a user

**POST** `/api/addReservation/{email}/{reservationId}`

### 3. Remove a reservation from a user

**PUT** `/api/removeReservation/{email}/{reservationId}`

### 4. Get all reservations

**GET** `/api/getAllReservation`

### 5. Get reservation by ID

**GET** `/api/getReservationById/{id}`

### 6. Update a reservation

**PUT** `/api/updateReservation/{id}`

### 7. Delete a reservation by ID

**DELETE** `/api/deleteReservationById/{id}`
