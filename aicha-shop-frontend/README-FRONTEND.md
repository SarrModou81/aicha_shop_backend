# AICHA SHOP - Frontend Angular 17

Application e-commerce complète développée avec Angular 17 (non-standalone) et SCSS.

## 🏗️ Architecture

### Structure du projet
```
src/app/
├── core/                   # Services, modèles, guards, interceptors
│   ├── models/            # Interfaces TypeScript
│   ├── services/          # Services (Auth, Product, Cart, Order)
│   ├── guards/            # Guards de routing
│   └── interceptors/      # HTTP Interceptor pour auth
├── shared/                # Composants réutilisables
│   └── components/        # Header, Footer, ProductCard, Loading
├── features/              # Modules fonctionnels
│   ├── auth/             # Login & Register
│   ├── client/           # Interface client (Home, Products, Cart, Orders)
│   ├── vendeur/          # Interface vendeur (Dashboard, Products, Stats)
│   └── admin/            # Interface admin (Users, Products, Settings)
└── environments/         # Configuration API

## 🚀 Installation et démarrage

### Prérequis
- Node.js v18+
- npm ou yarn
- Backend Laravel en cours d'exécution sur http://localhost:8000

### Installation
```bash
cd aicha-shop-frontend
npm install
```

### Configuration API
Modifiez `src/environments/environment.ts` pour pointer vers votre API Laravel:
```typescript
export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api'
};
```

### Démarrage du serveur de développement
```bash
npm start
# ou
ng serve
```

L'application sera accessible sur http://localhost:4200

### Build de production
```bash
npm run build
# ou
ng build --configuration=production
```

## 👥 Rôles et fonctionnalités

### CLIENT
- ✅ Navigation et recherche de produits
- ✅ Panier d'achat
- ✅ Passage de commandes
- ✅ Historique des commandes
- ✅ Profil utilisateur

### VENDEUR
- ✅ Tableau de bord avec statistiques
- ✅ Gestion du catalogue produits (CRUD)
- ✅ Gestion des commandes
- ✅ Gestion des stocks
- ✅ Rapports de ventes

### ADMINISTRATEUR
- ✅ Tableau de bord global
- ✅ Gestion des utilisateurs
- ✅ Modération des produits
- ✅ Gestion des catégories et marques
- ✅ Configuration système
- ✅ Logs de sécurité

## 🎨 Thème et Design

L'application utilise un thème personnalisé SCSS avec:
- Couleur primaire: #E91E63 (Rose)
- Couleur secondaire: #000000 (Noir)
- Design responsive
- Composants réutilisables

## 🔐 Authentification

L'application utilise Laravel Sanctum pour l'authentification:
- JWT stocké dans localStorage
- HTTP Interceptor pour ajouter le token automatiquement
- Guards de routing pour protéger les routes
- Redirection automatique selon le rôle

## 📝 Services principaux

- **AuthService**: Gestion de l'authentification
- **ProductService**: Gestion des produits
- **CartService**: Gestion du panier avec BehaviorSubject
- **OrderService**: Gestion des commandes

## 🛣️ Routing

- `/` - Page d'accueil
- `/auth/login` - Connexion
- `/auth/register` - Inscription
- `/products` - Liste des produits
- `/products/:slug` - Détail produit
- `/cart` - Panier (authentifié)
- `/checkout` - Paiement (authentifié)
- `/orders` - Mes commandes (authentifié)
- `/vendeur/*` - Interface vendeur (role: vendeur)
- `/admin/*` - Interface admin (role: admin)

## 🔧 Technologies utilisées

- Angular 17 (non-standalone)
- TypeScript
- SCSS
- RxJS
- Angular Router
- Reactive Forms
- HTTP Client

## 📦 Modules

- **AppModule**: Module racine
- **SharedModule**: Composants partagés
- **AuthModule**: Authentification (lazy loaded)
- **ClientModule**: Interface client (lazy loaded)
- **VendeurModule**: Interface vendeur (lazy loaded)
- **AdminModule**: Interface admin (lazy loaded)

## 🎯 Prochaines étapes

Pour continuer le développement:

1. Implémenter les pages de détail produit
2. Ajouter la recherche et les filtres avancés
3. Implémenter le système de paiement
4. Ajouter les notifications en temps réel
5. Implémenter les avis produits
6. Ajouter les statistiques avancées pour vendeurs
7. Implémenter l'upload d'images
8. Ajouter les tests unitaires et E2E

## 📄 License

© 2024 AICHA SHOP - Tous droits réservés
