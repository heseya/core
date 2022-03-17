<?php

use App\Enums\EventType;

return [
    EventType::class => [
        // Descriptions
        EventType::ORDER_CREATED => 'Zdarzenie wyzwalane po utworzeniu nowych zamówień',
        EventType::ORDER_UPDATED => 'Zdarzenie wyzwalane po aktualizacji zamówienia',
        EventType::ORDER_UPDATED_STATUS => 'Zdarzenie wyzwalane po aktualizacji statusu zamówienia',
        EventType::ORDER_REQUESTED_SHIPPING => 'Zdarzenie wyzwalane przy tworzeniu listu przewozowego',
        EventType::PRODUCT_CREATED => 'Zdarzenie wyzwalane po utworzeniu nowych produktów',
        EventType::PRODUCT_UPDATED => 'Zdarzenie wyzwalane po aktualizacji produktu',
        EventType::PRODUCT_DELETED => 'Zdarzenie wyzwalane po usunięciu produktu',
        EventType::ITEM_CREATED => 'Zdarzenie wyzwalane po utworzeniu nowych przedmiotów magazynowych',
        EventType::ITEM_UPDATED => 'Zdarzenie wyzwalane po aktualizacji przedmiotów magazynowych',
        EventType::ITEM_UPDATED_QUANTITY => 'Zdarzenie wyzwalane po aktualizacji ilości przedmiotów magazynowych',
        EventType::ITEM_DELETED => 'Zdarzenie wyzwalane po usunięciu przedmiotów magazynowych',
        EventType::PAGE_CREATED => 'Zdarzenie wyzwalane po utworzeniu nowych stron',
        EventType::PAGE_UPDATED => 'Zdarzenie wyzwalane po aktualizacji stron',
        EventType::PAGE_DELETED => 'Zdarzenie wyzwalane po usunięciu stron',
        EventType::PRODUCT_SET_CREATED => 'Zdarzenie wyzwalane po utworzeniu nowych kolekcji',
        EventType::PRODUCT_SET_UPDATED => 'Zdarzenie wyzwalane po aktualizacji kolekcji',
        EventType::PRODUCT_SET_DELETED => 'Zdarzenie wyzwalane po usunięciu kolekcji',
        EventType::USER_CREATED => 'Zdarzenie wyzwalane po utworzeniu nowych użytkowników',
        EventType::USER_UPDATED => 'Zdarzenie wyzwalane po aktualizacji użytkowników',
        EventType::USER_DELETED => 'Zdarzenie wyzwalane po usunięciu użytkowników',
        EventType::DISCOUNT_CREATED => 'Zdarzenie wyzwalane po utworzeniu nowych kodów rabatowych',
        EventType::DISCOUNT_UPDATED => 'Zdarzenie wyzwalane po aktualizacji kodów rabatowych',
        EventType::DISCOUNT_DELETED => 'Zdarzenie wyzwalane po usunięciu kodów rabatowych',
    ]
];
