<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    description: 'API-only, multi-tenant retail ERP. Every response uses the standard '
        .'envelope: {status, code, message, data}.',
    title: 'Rapid ERP Retail API',
)]
#[OA\Server(
    url: '/',
    description: 'Current host',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Passport access token. Send as: Authorization: Bearer {token}.',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
#[OA\Tag(
    name: 'Setup / Country',
    description: 'Setup → Country. Tenant country management plus the central '
        .'lookup master it draws from: pick countries, update tenant-owned fields, '
        .'soft/force delete, CSV import/export, and search the lookup master.',
)]
#[OA\Tag(
    name: 'Setup / State',
    description: 'Setup → State. Tenant state/province management plus the central '
        .'lookup master it draws from: pick states, update tenant-owned fields, '
        .'soft/force delete, CSV import/export, and search the lookup master.',
)]
#[OA\Tag(
    name: 'Setup / City',
    description: 'Setup → City. Tenant city management plus the central lookup '
        .'master it draws from: pick cities, update tenant-owned fields, '
        .'soft/force delete, CSV import/export, and search the lookup master.',
)]
#[OA\Tag(
    name: 'Setup / Area',
    description: 'Setup → Area. Tenant-defined areas/localities under a city '
        .'(custom data — address, post code, contact — NOT a central lookup): '
        .'create, update tenant-owned fields, soft/force delete, CSV import/export.',
)]
#[OA\Tag(
    name: 'Setup / Currency',
    description: 'Setup → Currency. Tenant currency management plus the central '
        .'lookup master it draws from: pick currencies, update tenant-owned fields, '
        .'soft/force delete, CSV import/export, and search the lookup master.',
)]
#[OA\Tag(
    name: 'Setup / Supplier',
    description: 'Setup → Supplier. Tenant-defined suppliers/vendors (custom data, '
        .'NOT a central lookup; optionally linked to the tenant\'s country/state/'
        .'currency): create, update, soft/force delete, CSV import/export.',
)]
#[OA\Tag(
    name: 'Setup / Customer',
    description: 'Setup → Customer. Tenant-defined customers (custom data, NOT a '
        .'central lookup; optionally linked to the tenant\'s country/state/currency): '
        .'create, update, soft/force delete, CSV import/export.',
)]
#[OA\Tag(
    name: 'Setup / Department',
    description: 'Setup → Department. Tenant-defined departments (custom data, a '
        .'flat list): create, update, soft/force delete, CSV import/export.',
)]
#[OA\Tag(
    name: 'Setup / Group',
    description: 'Setup → Group. Tenant-defined hierarchical classification groups '
        .'(nestable via parent_id): create, update, soft/force delete, CSV import/export.',
)]
#[OA\Tag(
    name: 'Setup / Designation',
    description: 'Setup → Designation. Tenant-defined job titles under a department '
        .'(department_id required): create, update, soft/force delete, CSV import/export.',
)]
#[OA\Tag(
    name: 'Setup / Employee',
    description: 'Setup → Employee. Tenant-defined employee profiles (require a '
        .'department + designation) with an inline documents list: create, update, '
        .'soft/force delete, CSV import/export.',
)]
abstract class Controller
{
    //
}
