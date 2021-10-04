<?php

use App\Enums\EventPermissionType;

return [
    EventPermissionType::class => [
        // Descriptions
        EventPermissionType::ORDER_CREATED[0] => 'Zdarzenie wyzwalane po utworzeniu nowych zamówień',
        EventPermissionType::ORDER_UPDATED[0] => 'Zdarzenie wyzwalane po aktualizacji zamówienia',
        EventPermissionType::ORDER_DELETED[0] => 'Zdarzenie wyzwalane po usunięciu zamówienia',
        EventPermissionType::PRODUCT_CREATED[0] => 'Zdarzenie wyzwalane po utworzeniu nowych produktów',
        EventPermissionType::PRODUCT_UPDATED[0] => 'Zdarzenie wyzwalane po aktualizacji produktu',
        EventPermissionType::PRODUCT_DELETED[0] => 'Zdarzenie wyzwalane po usunięciu produktu',
        EventPermissionType::ITEM_CREATED[0] => 'Zdarzenie wyzwalane po utworzeniu nowych przedmiotów magazynowych',
        EventPermissionType::ITEM_UPDATED[0] => 'Zdarzenie wyzwalane po aktualizacji przedmiotów magazynowych',
        EventPermissionType::ITEM_UPDATED_QUANTITY[0] => 'Zdarzenie wyzwalane po aktualizacji ilości przedmiotów magazynowych',
        EventPermissionType::ITEM_DELETED[0] => 'Zdarzenie wyzwalane po usunięciu przedmiotów magazynowych',
        EventPermissionType::PAGE_CREATED[0] => 'Zdarzenie wyzwalane po utworzeniu nowych stron',
        EventPermissionType::PAGE_UPDATED[0] => 'Zdarzenie wyzwalane po aktualizacji stron',
        EventPermissionType::PAGE_DELETED[0] => 'Zdarzenie wyzwalane po usunięciu stron',
        EventPermissionType::PRODUCT_SET_CREATED[0] => 'Zdarzenie wyzwalane po utworzeniu nowych kolekcji',
        EventPermissionType::PRODUCT_SET_UPDATED[0] => 'Zdarzenie wyzwalane po aktualizacji kolekcji',
        EventPermissionType::PRODUCT_SET_DELETED[0] => 'Zdarzenie wyzwalane po usunięciu kolekcji',
        EventPermissionType::USER_CREATED[0] => 'Zdarzenie wyzwalane po utworzeniu nowych użytkowników',
        EventPermissionType::USER_UPDATED[0] => 'Zdarzenie wyzwalane po aktualizacji użytkowników',
        EventPermissionType::USER_DELETED[0] => 'Zdarzenie wyzwalane po usunięciu użytkowników',
        EventPermissionType::DISCOUNT_CREATED[0] => 'Zdarzenie wyzwalane po utworzeniu nowych kodów rabatowych',
        EventPermissionType::DISCOUNT_UPDATED[0] => 'Zdarzenie wyzwalane po aktualizacji kodów rabatowych',
        EventPermissionType::DISCOUNT_DELETED[0] => 'Zdarzenie wyzwalane po usunięciu kodów rabatowych',
    ]
];
