erDiagram



USERS ||--o{ ROLE\_USER : has

ROLES ||--o{ ROLE\_USER : assigns



ROLES ||--o{ PERMISSION\_ROLE : grants

PERMISSIONS ||--o{ PERMISSION\_ROLE : includes



USERS ||--o{ PERMISSION\_USER : overrides

PERMISSIONS ||--o{ PERMISSION\_USER : assigned



MODULES ||--o{ PERMISSIONS : contains

ACTIONS ||--o{ PERMISSIONS : defines



USERS }o--|| USERS\_STATUS : has

USERS }o--|| DEPARTMENTS : belongs

USERS }o--o{ USER\_DEPARTMENT : mapped



WAREHOUSES ||--o{ WAREHOUSE\_USER : manages

USERS ||--o{ WAREHOUSE\_USER : works



CUSTOMERS ||--o{ ORDERS : places

ORDERS ||--o{ ORDER\_ITEMS : contains

PRODUCTS ||--o{ ORDER\_ITEMS : included



ORDERS ||--o{ PAYMENTS : paid\_by

ORDERS ||--|| SHIPMENTS : ships



WAREHOUSES ||--o{ INVENTORIES : holds

PRODUCTS ||--o{ INVENTORIES : tracked



WAREHOUSES ||--o{ STOCK\_IN : receives

STOCK\_IN ||--o{ STOCK\_IN\_ITEMS : includes



WAREHOUSES ||--o{ STOCK\_OUT : releases

STOCK\_OUT ||--o{ STOCK\_OUT\_ITEMS : includes



PRODUCTS }o--|| UNITS : measured\_by

PRODUCTS }o--|| STATUSES : has



