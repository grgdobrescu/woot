# Woot PRO - OpenCart Shipping Extension

Integrate Woot shipping services into your OpenCart 4.x store. Get real-time shipping quotes, generate AWBs, and offer delivery to addresses or pickup points (lockers/shops).

## Features

- **Real-time Shipping Quotes** - Get live prices from Woot API based on destination and parcel weight
- **Multiple Shipping Services** - Configure different courier services with custom display names
- **Flexible Pricing** - Choose between API quotation or fixed pricing per service
- **Price Markup** - Add percentage and/or fixed markup to shipping costs
- **Delivery Options** - Support for home delivery and locker/pickup point delivery
- **AWB Generation** - Generate shipping labels directly from order page
- **Shipped Notifications** - Automatically update order status and notify customers when AWB is generated
- **COD/Repayment Support** - Configure which payment methods require cash on delivery
- **Nomenclature Sync** - Synchronize countries, counties, cities, and pickup locations from Woot API
- **City Dropdown** - Enhanced checkout with searchable city dropdown
- **Location Picker** - Interactive map/list for selecting pickup points
- **Multi-language** - English and Romanian translations included

## Requirements

- OpenCart 4.0.0 or higher
- PHP 8.0 or higher
- Woot API credentials (Public Key and Secret Key)

## Installation

1. Download `woot.ocmod.zip`
2. Go to **Extensions > Installer** in OpenCart admin
3. Click **Upload** and select the zip file
4. Go to **Extensions > Extensions > Shipping**
5. Find **Woot Shipping** and click **Install**
6. Click **Edit** to configure

## Configuration

### 1. Connect to Woot API

Enter your Woot API credentials:
- **Public Key** - Your Woot API public key
- **Secret Key** - Your Woot API secret key

Click **Connect** to verify credentials.

### 2. Select Pickup Address

Choose the default sender address for AWB generation.

### 3. Select Default Parcel

Choose the default parcel configuration (dimensions/weight) for shipments.

### 4. Configure Shipping Services

Add shipping services from the available options:
- **Delivery to Address** - Standard home delivery services
- **Delivery to Locker/Pickup Point** - Easybox, shop pickup, etc.

For each service, configure:
- **Display Name** - Custom name shown in checkout
- **Price Type** - Quotation (from API) or Fixed price
- **Markup %** - Percentage markup on API price
- **Markup Fixed** - Fixed amount markup

### 5. Shipping Settings

- **Prices Include VAT** - Enable if you want to display prices with VAT included directly from the API. When enabled, the Tax Class setting is ignored.
- **Tax Class** - Select tax class for shipping (used when "Prices Include VAT" is disabled)
- **Geo Zone** - Restrict shipping to specific geographic zones

### 6. Shipped Notification

- **Order Status After AWB** - Automatically set this status when AWB is generated
- **Notification Message** - Email template with placeholders: `{courier_name}`, `{awb}`, `{tracking_url}`

### 7. Repayment (COD) Settings

Select payment methods that require cash on delivery. The order total will be sent to the courier for collection.

## Usage

### Checkout

Customers will see configured shipping services at checkout. For locker delivery services, a location picker allows selecting the pickup point.

### Order Management

From the order info page in admin:
1. View the **Woot Shipping** card
2. Click **Generate AWB** to create the shipping label
3. The order status will be updated and customer notified (if configured)

### Nomenclature

Go to **Woot PRO > Nomenclature** to:
- Sync countries, counties, cities from Woot API
- Map Woot locations to OpenCart zones
- Sync locker/pickup point locations

## VAT Pricing Options

The module supports two pricing modes:

| Setting | Price Source | Tax Handling |
|---------|--------------|--------------|
| **Prices Include VAT: OFF** (default) | Uses `price` (net) from API | OpenCart adds tax based on Tax Class |
| **Prices Include VAT: ON** | Uses `total` (gross) from API | No additional tax applied |

Use "Prices Include VAT: ON" if:
- Your store displays all prices with VAT included
- You don't want to configure OpenCart tax classes for shipping

## Support

For support, visit [https://pro.woot.ro](https://pro.woot.ro)

## Changelog

### 1.0.0
- Initial release
- Real-time shipping quotes
- AWB generation
- Locker/pickup point delivery
- Nomenclature synchronization
- Shipped notifications
- COD/Repayment support
- VAT-inclusive pricing option
