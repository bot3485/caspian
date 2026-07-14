# 🌊 Caspian v3.1 — Video-Chat & Live Matching Ecosystem

Caspian is a high-performance, real-time video-chat roulette and messaging platform built with **Laravel 11+**, **Tailwind CSS**, **Alpine.js**, **WebRTC**, **Redis**, and **Pusher (Laravel Echo)**. It provides frictionless personal call connections, smart geolocation/interest-based matching, and persistent real-time messaging.

---

## 🚀 What's New in v3.1 (Major Release)

Version 3.1 introduces a major architectural overhaul focusing on call synchronization reliability, unified messaging architecture, adaptive temporal displays, and an optimized social UX in the Floating Control Island.

### 🌟 Key Highlights
*   **Zero-State Call Synchronization:** Rewritten background database sweeps that prevent stale caller states and erroneous "BUSY" blocks.
*   **Unified Context-Aware Chat Routing:** Intelligently redirects active roulette matches into persistent private contact threads to eliminate duplicate chats.
*   **Adaptive Date-Time Engines:** Dynamic message timestamps that scale automatically from precise minutes to relative days, months, and historical years.
*   **Frictionless Floating Control Island:** Dynamically strips redundant UI controls (e.g., identity linking, report flags) during direct personal calls while preserving full functionality in discovery mode.

---

## 🛠 Tech Stack

*   **Backend:** Laravel (PHP 8.2+) with Eloquent ORM
*   **Real-time Engine:** Laravel Echo, Pusher / Soketi, Redis (Key-value store & lists)
*   **Frontend Engine:** Alpine.js (Lightweight reactive state wrapper)
*   **Video Delivery:** WebRTC (Peer-to-Peer connection architecture)
*   **Style Framework:** Tailwind CSS with fluid grid styling

---

## 📂 Core Architecture Overviews

### 1. Zero-State Call Resolution (`LeaveChat.php`)
Ensures that any abrupt disconnects, page reloads, or navigation switches instantly clean up connections on both the active caller side and the passive receiver side. 

*   Stops the heavy use of expensive `Redis::keys`.
*   Triggers automatic socket-based `peer-disconnected` packets.
*   Ensures that consecutive outgoing requests do not trip over lingering database records.

### 2. Tailored Messaging Router (`getChatHistory`)
Improves history queries to fetch the most recent subset of a database table and reverse it on the collection layer before serving JSON payloads:
```php
$messages = Message::where(...)
    ->orderBy('id', 'desc')
    ->take(100)
    ->get()
    ->reverse()
    ->values();
Guarantees new messages never drop out of sight when total database history surpasses 100 entries.

⚙️ Installation & Deployment
Prerequisites
PHP 8.2+

Node.js & NPM

Redis Server

Composer

Quick Start
Clone the repository:

Bash
git clone [https://github.com/your-username/caspian.git](https://github.com/your-username/caspian.git)
cd caspian
Install dependencies:

Bash
composer install
npm install
Configure environment variables:

Bash
cp .env.example .env
php artisan key:generate
Run migrations and seeds:

Bash
php artisan migrate --seed
Start development engines:

Bash
# Run the asset compiler
npm run dev

# Run the local server
php artisan serve

# Run queue workers (needed for broadcast events)
php artisan queue:work