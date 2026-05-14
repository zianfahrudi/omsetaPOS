<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kasir - omsetaPOS</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg: #f3f4f8;
            --panel: #ffffff;
            --ink: #1e293b;
            --muted: #64748b;
            --line: #e2e8f0;
            --soft: #f1f5f9;
            --brand: #6366f1;
            /* Indigo */
            --brand-dark: #4f46e5;
            --brand-light: #e0e7ff;
            --danger: #ef4444;
            --danger-soft: #fee2e2;
            --success: #10b981;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --radius-sm: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            min-height: 100vh;
            background: var(--bg);
            color: var(--ink);
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        button,
        input,
        select {
            font: inherit;
        }

        .shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 340px 380px;
            height: 100dvh;
            overflow: hidden;
        }

        .shell>* {
            min-height: 0;
        }

        /* --- WORKSPACE --- */
        .workspace {
            display: flex;
            flex-direction: column;
            min-width: 0;
            min-height: 0;
            height: 100%;
            background: var(--bg);
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 24px 32px 16px;
            background: var(--bg);
            z-index: 10;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .brand-mark {
            display: grid;
            width: 48px;
            height: 48px;
            place-items: center;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            font-size: 18px;
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .brand h1 {
            font-size: 24px;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.02em;
            color: var(--ink);
        }

        .brand p {
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
            margin-top: 2px;
        }

        .topbar-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .dashboard-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 999px;
            background: var(--panel);
            color: var(--ink);
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid var(--line);
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            cursor: pointer;
        }

        .dashboard-link:hover {
            border-color: var(--brand);
            color: var(--brand);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .filters {
            display: grid;
            grid-template-columns: 220px minmax(260px, 1fr);
            gap: 16px;
            padding: 0 32px 24px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field span {
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input,
        .select {
            width: 100%;
            height: 48px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 0 16px;
            background: var(--panel);
            color: var(--ink);
            font-size: 15px;
            font-weight: 500;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            appearance: none;
        }

        .select {
            padding-right: 40px;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 20px;
        }

        .input:focus,
        .select:focus {
            outline: none;
            border-color: var(--brand);
            box-shadow: 0 0 0 4px var(--brand-light);
        }

        .catalog-container {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding: 0 32px 32px;
            overscroll-behavior: contain;
        }

        .catalog {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .product {
            position: relative;
            display: flex;
            flex-direction: column;
            border: none;
            border-radius: var(--radius-lg);
            background: var(--panel);
            cursor: pointer;
            text-align: left;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            outline: none;
        }

        .product::after {
            content: '';
            position: absolute;
            inset: 0;
            border: 2px solid transparent;
            border-radius: var(--radius-lg);
            transition: var(--transition);
            pointer-events: none;
        }

        .product:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .product:focus-visible::after {
            border-color: var(--brand);
        }

        .product:disabled {
            cursor: not-allowed;
            opacity: 0.6;
            filter: grayscale(0.5);
        }

        .product:disabled:hover {
            transform: none;
            box-shadow: var(--shadow-sm);
        }

        .product-image {
            position: relative;
            width: 100%;
            aspect-ratio: 4 / 3;
            background: var(--soft);
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product:hover:not(:disabled) .product-image img {
            transform: scale(1.05);
        }

        .product-fallback {
            display: grid;
            width: 100%;
            height: 100%;
            place-items: center;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            color: var(--muted);
            font-size: 48px;
            font-weight: 800;
        }

        .product-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .product-name {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.4;
            color: var(--ink);
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-code {
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        .product-foot {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            margin-top: auto;
            padding-top: 16px;
        }

        .price {
            color: var(--brand);
            font-size: 18px;
            font-weight: 800;
            line-height: 1;
        }

        .stock {
            margin-top: 6px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .stock.low {
            color: var(--danger);
        }

        .add-pill {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--brand-light);
            color: var(--brand-dark);
            transition: var(--transition);
        }

        .add-pill svg {
            width: 20px;
            height: 20px;
        }

        .product:hover:not(:disabled) .add-pill {
            background: var(--brand);
            color: white;
            transform: scale(1.1);
        }

        /* --- ORDER PANEL --- */
        .cart-panel,
        .checkout-panel {
            display: flex;
            flex-direction: column;
            height: 100dvh;
            min-height: 0;
            background: var(--panel);
            z-index: 20;
        }

        .checkout-panel {
            background: #f8fafc;
            border-left: 1px solid var(--line);
            box-shadow: -4px 0 24px rgba(0, 0, 0, 0.04);
        }

        .order-head {
            padding: 24px;
            border-bottom: 1px solid var(--line);
        }

        .order-head-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .order-head h2 {
            font-size: 20px;
            font-weight: 800;
            color: var(--ink);
        }

        .order-head p {
            margin-top: 4px;
            color: var(--brand);
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .badge {
            background: var(--brand);
            color: white;
            padding: 2px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
        }

        .reset-btn {
            padding: 8px 16px;
            border-radius: 999px;
            background: var(--danger-soft);
            color: var(--danger);
            font-weight: 700;
            font-size: 13px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
        }

        .reset-btn:hover {
            background: var(--danger);
            color: white;
        }

        .cart {
            flex: 1;
            overflow-y: auto;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--muted);
            text-align: center;
        }

        .empty svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            color: var(--line);
        }

        .empty p {
            font-size: 16px;
            font-weight: 600;
        }

        .cart-item {
            display: grid;
            grid-template-columns: 64px 1fr;
            gap: 16px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--panel);
            transition: var(--transition);
        }

        .cart-item:hover {
            border-color: var(--brand-light);
            box-shadow: var(--shadow-sm);
        }

        .cart-thumb {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-md);
            background: var(--soft);
            overflow: hidden;
        }

        .cart-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 4px;
        }

        .cart-title {
            font-weight: 700;
            font-size: 15px;
            line-height: 1.3;
            color: var(--ink);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .cart-meta {
            margin-top: 4px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        .cart-control-row {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-top: 12px;
        }

        .qty {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--soft);
            padding: 4px;
            border-radius: 999px;
        }

        .icon-btn {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: none;
            background: var(--panel);
            color: var(--ink);
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .icon-btn:hover {
            color: var(--brand);
            transform: scale(1.05);
        }

        .qty strong {
            font-size: 14px;
            font-weight: 700;
            min-width: 20px;
            text-align: center;
        }

        .item-total {
            font-weight: 800;
            font-size: 15px;
            color: var(--brand);
        }

        .order-total {
            padding: 24px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .customer-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .discount-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 10px;
            margin-bottom: 20px;
            align-items: end;
        }

        .small-btn {
            height: 48px;
            padding: 0 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--panel);
            color: var(--ink);
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
        }

        .small-btn:hover {
            border-color: var(--brand);
            color: var(--brand);
        }

        .summary {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
            padding: 16px;
            background: var(--panel);
            border-radius: var(--radius-lg);
            border: 1px solid var(--line);
        }

        .summary-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: var(--muted);
            font-size: 14px;
            font-weight: 600;
        }

        .summary-line strong {
            color: var(--ink);
            font-size: 16px;
            font-weight: 700;
        }

        .grand {
            margin-top: 4px;
            padding-top: 16px;
            border-top: 1px dashed var(--line);
            color: var(--ink);
        }

        .grand strong {
            font-size: 24px;
            font-weight: 800;
            color: var(--brand);
        }

        .pay-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            grid-column: 1 / -1;
        }

        .payment-card {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px;
            border: 2px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--panel);
            color: var(--muted);
            cursor: pointer;
            transition: var(--transition);
            font-weight: 700;
            font-size: 14px;
        }

        .payment-card:hover {
            border-color: var(--brand-light);
            background: rgba(99, 102, 241, 0.05);
        }

        .payment-card.active {
            border-color: var(--brand);
            background: rgba(99, 102, 241, 0.1);
            color: var(--brand);
        }

        .debt-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            grid-column: 1 / -1;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--panel);
            color: var(--ink);
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
        }

        .debt-toggle input {
            width: 18px;
            height: 18px;
            accent-color: var(--brand);
        }

        .primary-btn {
            width: 100%;
            height: 56px;
            flex-shrink: 0;
            border: none;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-dark) 100%);
            color: white;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .primary-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
        }

        .primary-btn:active:not(:disabled) {
            transform: translateY(0);
        }

        .primary-btn:disabled {
            background: var(--muted);
            box-shadow: none;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .toast {
            position: fixed;
            right: 24px;
            bottom: 24px;
            z-index: 100;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            background: var(--ink);
            color: white;
            font-weight: 600;
            font-size: 14px;
            box-shadow: var(--shadow-lg);
            transform: translateY(0);
            opacity: 1;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .toast.success {
            background: var(--success);
        }

        .toast.error {
            background: var(--danger);
        }

        .toast.hidden {
            transform: translateY(120%);
            opacity: 0;
        }

        .summary-btn {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 10px 12px;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .summary-btn:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .summary-btn-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            flex-shrink: 0;
        }

        .summary-btn-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            min-width: 0;
        }

        .summary-btn-label {
            font-size: 11px;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .summary-btn-value {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            width: 100%;
        }

        .modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 200;
        }

        .modal-backdrop.hidden {
            display: none;
        }

        .modal {
            background: white;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 500px;
            box-shadow: var(--shadow-xl);
            display: flex;
            flex-direction: column;
            max-height: 80vh;
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 16px;
            font-weight: 600;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-light);
        }

        .modal-body {
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            overflow-y: auto;
        }

        .customer-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .customer-item {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s;
        }

        .customer-item:hover {
            border-color: var(--primary);
            background: rgba(99, 102, 241, 0.05);
        }

        .customer-item h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .customer-item p {
            font-size: 12px;
            color: var(--text-light);
            margin: 0;
        }

        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .modal-search-input {
            height: 48px !important;
            min-height: 48px;
            padding: 0 16px !important;
            font-size: 15px;
            line-height: 48px;
            appearance: none;
            -webkit-appearance: none;
        }

        .transaction-item {
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            padding: 12px;
            background: var(--panel);
            cursor: pointer;
            transition: var(--transition);
        }

        .transaction-item:hover {
            border-color: var(--brand);
            box-shadow: var(--shadow-sm);
        }

        .transaction-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .transaction-number {
            font-size: 14px;
            font-weight: 800;
            color: var(--ink);
        }

        .transaction-meta,
        .transaction-items {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .transaction-total {
            color: var(--brand);
            font-size: 15px;
            font-weight: 800;
            white-space: nowrap;
        }

        .transaction-status {
            display: inline-flex;
            align-items: center;
            margin-top: 6px;
            padding: 3px 8px;
            border-radius: 999px;
            background: #dcfce7;
            color: #166534;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .transaction-status.refund {
            background: #fee2e2;
            color: #991b1b;
        }

        .transaction-status.debt {
            background: #fef3c7;
            color: #92400e;
        }

        .refund-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .refund-panel {
            display: flex;
            flex-direction: column;
            gap: 10px;
            min-height: 0;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: #f8fafc;
        }

        .refund-panel h4 {
            font-size: 13px;
            font-weight: 800;
            color: var(--ink);
        }

        .refund-sale,
        .refund-line {
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: var(--panel);
        }

        .refund-sale {
            cursor: pointer;
            transition: var(--transition);
        }

        .refund-sale:hover,
        .refund-sale.active {
            border-color: var(--brand);
            box-shadow: var(--shadow-sm);
        }

        .refund-sale.disabled {
            cursor: not-allowed;
            opacity: 0.55;
            filter: grayscale(0.35);
        }

        .refund-sale.disabled:hover {
            border-color: var(--line);
            box-shadow: none;
        }

        .refund-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 78px;
            gap: 10px;
            align-items: center;
        }

        .refund-line input {
            height: 38px;
            border: 1px solid var(--line);
            border-radius: var(--radius-sm);
            padding: 0 10px;
            background: var(--panel);
            color: var(--ink);
            font-weight: 700;
        }

        .refund-summary {
            display: grid;
            gap: 8px;
            padding: 12px;
            border-radius: var(--radius-md);
            background: var(--panel);
            border: 1px dashed var(--line);
        }

        .refund-product-body {
            position: relative;
            gap: 14px;
            padding-bottom: 0;
        }

        .refund-product-search {
            flex: 0 0 48px;
            width: 100%;
        }

        .refund-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 12px;
            padding-bottom: 82px;
        }

        .refund-product-card {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: var(--radius-lg);
            background: var(--panel);
            cursor: pointer;
            transition: var(--transition);
        }

        .refund-product-card:hover,
        .refund-product-card:has(input[type="checkbox"]:checked) {
            border-color: var(--brand);
            box-shadow: var(--shadow-sm);
        }

        .refund-product-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            accent-color: var(--brand);
            cursor: pointer;
            flex-shrink: 0;
        }

        .refund-product-card-content {
            flex: 1;
            min-width: 0;
        }

        .refund-product-card-title {
            font-weight: 600;
            font-size: 15px;
            color: var(--text);
            margin-bottom: 4px;
            line-height: 1.4;
        }

        .refund-product-card-meta {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .refund-product-card-price {
            font-weight: 700;
            color: var(--text);
        }

        .refund-product-card-qty {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .refund-product-card-qty span {
            font-size: 13px;
            font-weight: 600;
            color: var(--muted);
        }

        .refund-product-card .input {
            width: 90px;
            height: 38px;
            min-height: 38px;
            padding: 0 12px;
            text-align: center;
            border-radius: var(--radius-md);
            font-weight: 600;
        }

        .refund-product-actions {
            position: sticky;
            bottom: -16px;
            z-index: 5;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin: 4px -20px -16px;
            padding: 18px 20px 16px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.72), #fff 42%);
            border-top: 1px solid var(--line);
            backdrop-filter: blur(8px);
        }

        @media (max-width: 860px) {
            .refund-grid {
                grid-template-columns: 1fr;
            }

            .refund-product-grid {
                grid-template-columns: 1fr;
            }
        }

        .receipt {
            width: 320px;
            max-width: 100%;
            margin: 0 auto;
            padding: 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius-md);
            background: #fff;
            color: #111827;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
        }

        .receipt-head {
            text-align: center;
            border-bottom: 1px dashed #94a3b8;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }

        .receipt-head h3 {
            font-size: 16px;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .receipt-line,
        .receipt-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 12px;
            line-height: 1.5;
        }

        .receipt-items {
            border-top: 1px dashed #94a3b8;
            border-bottom: 1px dashed #94a3b8;
            margin: 10px 0;
            padding: 8px 0;
        }

        .receipt-item {
            margin-bottom: 8px;
        }

        .receipt-item-name {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
        }

        .receipt-total {
            font-weight: 800;
            font-size: 14px;
        }

        .receipt-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .receipt-print-btn {
            height: 44px;
        }

        /* Scrollbar styles */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @media (max-width: 1024px) {
            .shell {
                grid-template-columns: 1fr;
                height: auto;
                min-height: 100vh;
            }

            .order-panel {
                height: auto;
            }

            .cart {
                max-height: 50vh;
            }
        }

        @media (max-width: 640px) {
            .topbar {
                padding: 16px;
                flex-direction: column;
                gap: 16px;
                align-items: stretch;
            }

            .topbar-actions {
                justify-content: stretch;
            }

            .dashboard-link {
                text-align: center;
                flex: 1;
            }

            .filters {
                padding: 0 16px 16px;
                grid-template-columns: 1fr;
            }

            .catalog-container {
                padding: 0 16px 24px;
            }

            .catalog {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }

            .customer-grid,
            .pay-grid,
            .discount-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <main class="shell" data-cashier data-products-url="{{ route('cashier.products') }}"
        data-transactions-url="{{ route('cashier.transactions') }}"
        data-mark-paid-url="{{ route('cashier.transactions.mark-paid', ['sale' => 0]) }}"
        data-refunds-url="{{ route('cashier.refunds.store') }}" data-customers-url="{{ route('cashier.customers') }}"
        data-customers-store-url="{{ route('cashier.customers.store') }}"
        data-customer-check-url="{{ route('cashier.customers.check') }}"
        data-pricing-url="{{ route('cashier.pricing') }}" data-checkout-url="{{ route('cashier.checkout') }}"
        data-csrf="{{ csrf_token() }}">
        <section class="workspace">
            <div class="topbar">
                <div class="brand">
                    <div class="brand-mark">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z" />
                            <path d="M3 6h18" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                    </div>
                    <div>
                        <h1>Kasir omsetaPOS</h1>
                        <p>{{ $user->name }} &middot; Selesaikan transaksi dengan cepat.</p>
                    </div>
                </div>
                <div class="topbar-actions">
                    <button class="dashboard-link" type="button" id="btn-open-refund">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                            <path d="M21 12a9 9 0 1 1-3-6.7" />
                            <path d="M21 3v6h-6" />
                        </svg>
                        Refund
                    </button>
                    <button class="dashboard-link" type="button" id="btn-open-transactions">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                            <path d="M3 3v18h18" />
                            <path d="M18 17V9" />
                            <path d="M13 17V5" />
                            <path d="M8 17v-3" />
                        </svg>
                        Riwayat Transaksi
                    </button>
                    <a class="dashboard-link" href="/admin">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                            <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                            <line x1="3" x2="21" y1="9" y2="9" />
                            <line x1="9" x2="9" y1="21" y2="9" />
                        </svg>
                        Dashboard Admin
                    </a>
                </div>
            </div>

            <div class="filters">
                <label class="field">
                    <span>Cabang Toko</span>
                    <select class="select" id="store">
                        @foreach ($stores as $store)
                            <option value="{{ $store->id }}">{{ $store->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Cari Produk</span>
                    <div class="input-group">
                        <input class="input" id="search" type="search" placeholder="Ketik nama produk atau SKU...">
                    </div>
                </label>
                {{-- Barcode scan belum dipakai.
                <label class="field">
                    <span>Scan Barcode</span>
                    <div class="input-group">
                        <input class="input" id="scan" type="text" placeholder="Arahkan scanner kesini..." autofocus>
                    </div>
                </label>
                --}}
            </div>

            <div class="catalog-container">
                <div class="catalog" id="catalog"></div>
            </div>
        </section>

        <aside class="cart-panel">
            <header class="order-head">
                <div class="order-head-row">
                    <div>
                        <h2>Pesanan Saat Ini</h2>
                        <p>Keranjang <span class="badge" id="item-count">0</span></p>
                    </div>
                    <button class="reset-btn" type="button" id="reset">
                        Kosongkan
                    </button>
                </div>
            </header>

            <section class="cart" id="cart"></section>
        </aside>

        <aside class="checkout-panel">
            <header class="order-head" style="border-bottom: 1px solid var(--line); padding-bottom: 16px;">
                <h2>Detail Transaksi</h2>
            </header>
            <div class="order-total" id="order-total-footer">
                <div id="order-details-container" style="flex: 1;">
                    <!-- Data Pelanggan -->
                    <div style="margin-bottom: 20px;">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 14px; font-weight: 600;">Data Pelanggan</span>
                        </div>
                        <div style="display: flex; gap: 8px; position: relative; margin-bottom: 12px;">
                            <input type="search" id="customer-search" class="input"
                                placeholder="Cari nama atau no. hp dari database..." autocomplete="off">
                            <button type="button" class="small-btn" id="btn-open-manual-customer"
                                style="width: 48px; flex-shrink: 0; padding: 0;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 5v14M5 12h14" />
                                </svg>
                            </button>
                            <div id="customer-list" class="customer-list"
                                style="position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 50; background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius-md); box-shadow: var(--shadow-lg); max-height: 200px; overflow-y: auto; display: none; flex-direction: column; gap: 8px; padding: 8px;">
                                <!-- Customer list rendered here -->
                            </div>
                        </div>
                    </div>

                    <!-- Kode Diskon -->
                    <div class="discount-row"
                        style="margin-bottom: 24px; display: flex; gap: 8px; align-items: flex-end;">
                        <label class="field" style="flex: 1;">
                            <span>Kode Diskon</span>
                            <input class="input" id="discount-code" type="text" placeholder="Contoh: HEMAT10">
                        </label>
                        <button class="small-btn" type="button" id="apply-discount">Apply</button>
                        <button class="small-btn" type="button" id="clear-discount">Hapus</button>
                    </div>

                    <div class="summary">
                        <div class="summary-line">
                            <span>Subtotal</span>
                            <strong id="subtotal">Rp 0</strong>
                        </div>
                        <div class="summary-line" id="discount-row-display" style="display: none;">
                            <span>Diskon</span>
                            <strong id="discount-total">Rp 0</strong>
                        </div>
                        <div class="summary-line">
                            <span>Service fee <small id="service-fee-rate">(0%)</small></span>
                            <strong id="service-fee-total">Rp 0</strong>
                        </div>
                        <div class="summary-line">
                            <span>Tax <small id="tax-rate">(0%)</small></span>
                            <strong id="tax-total">Rp 0</strong>
                        </div>
                        <div class="summary-line">
                            <span>Kembalian</span>
                            <strong id="change" style="color: var(--success);">Rp 0</strong>
                        </div>
                        <div class="summary-line" id="debt-row-display" style="display: none;">
                            <span>Hutang</span>
                            <strong id="debt-total" style="color: var(--danger);">Rp 0</strong>
                        </div>
                        <div class="summary-line grand">
                            <span>Total Pembayaran</span>
                            <strong id="grand-total">Rp 0</strong>
                        </div>
                    </div>

                    <div class="pay-grid">
                        <label class="field" style="grid-column: 1 / -1; margin-bottom: -4px;">
                            <span>Metode Pembayaran</span>
                        </label>
                        <input type="hidden" id="payment-method" value="cash">
                        <div class="payment-methods">
                            <div class="payment-card active" data-method="cash">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="2" y="6" width="20" height="12" rx="2" />
                                    <circle cx="12" cy="12" r="2" />
                                    <path d="M6 12h.01M18 12h.01" />
                                </svg>
                                Tunai
                            </div>
                            <div class="payment-card" data-method="qris">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                    <rect x="7" y="7" width="3" height="3" />
                                    <rect x="14" y="7" width="3" height="3" />
                                    <rect x="7" y="14" width="3" height="3" />
                                    <path d="M14 14h3v3h-3z" />
                                </svg>
                                QRIS
                            </div>
                        </div>
                        <label class="field" id="paid-field" style="grid-column: 1 / -1;">
                            <span id="paid-label">Nominal Diterima</span>
                            <input class="input" id="paid-amount" type="text" inputmode="numeric" autocomplete="off"
                                placeholder="0">
                        </label>
                        <label class="field" id="proof-field" style="display: none; grid-column: 1 / -1;">
                            <span>Upload Bukti Transfer</span>
                            <input class="input" id="payment-proof" type="file" accept="image/*" style="padding: 10px;">
                        </label>
                        <label class="debt-toggle">
                            <input id="is-debt" type="checkbox">
                            <span>Transaksi hutang piutang</span>
                        </label>
                    </div>
                </div>

                <button class="primary-btn" type="button" id="checkout" disabled style="margin-top: auto;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                    Proses Pembayaran
                </button>
            </div>
        </aside>
    </main>

    <div class="toast hidden" id="toast"></div>

    <div id="receipt-modal" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 420px;">
            <header class="modal-header">
                <h3>Detail Transaksi</h3>
                <button type="button" id="close-receipt-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <div id="receipt-content" class="receipt"></div>
                <div class="receipt-actions">
                    <button type="button" id="mark-sale-paid" class="small-btn" style="height:44px; display:none;">
                        Set Lunas
                    </button>
                    <button type="button" id="print-receipt" class="primary-btn receipt-print-btn">
                        Print Nota
                    </button>
                    <button type="button" id="finish-receipt" class="small-btn" style="height:44px;">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="transaction-modal" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 720px;">
            <header class="modal-header">
                <h3>Riwayat Transaksi</h3>
                <button type="button" id="close-transaction-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <input type="search" id="transaction-search" class="input modal-search-input"
                    placeholder="Cari customer, nomor transaksi, atau nama barang...">
                <div id="transaction-list" class="transaction-list"></div>
            </div>
        </div>
    </div>

    <div id="refund-modal" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 980px;">
            <header class="modal-header">
                <h3>Refund Transaksi</h3>
                <button type="button" id="close-refund-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <div class="refund-grid">
                    <section class="refund-panel">
                        <h4>Cari transaksi</h4>
                        <input type="search" id="refund-search" class="input modal-search-input"
                            placeholder="Nomor transaksi, customer, atau barang...">
                        <div id="refund-sale-list" class="transaction-list"></div>
                    </section>
                    <section class="refund-panel">
                        <h4>Detail refund</h4>
                        <label class="field">
                            <span>Tipe refund</span>
                            <select class="select" id="refund-type">
                                <option value="full">Full refund</option>
                                <option value="exchange">Ganti barang</option>
                            </select>
                        </label>
                        <div id="refund-selected-sale"
                            style="text-align:center; padding:20px; color:var(--muted); border:1px dashed var(--line); border-radius:var(--radius-md);">
                            Pilih transaksi dulu.
                        </div>
                        <div id="refund-return-list" style="display:grid; gap:10px;"></div>
                        <div id="refund-replacement-section" style="display:none; gap:10px; flex-direction:column;">
                            <button class="small-btn" type="button" id="open-refund-products"
                                style="height:44px; text-align:center;">
                                Pilih Barang Pengganti
                            </button>
                            <div id="refund-replacement-cart" style="display:grid; gap:10px;"></div>
                            <label class="field">
                                <span>Tambahan pembayaran</span>
                                <input class="input" id="refund-additional-payment" type="text" inputmode="numeric"
                                    autocomplete="off" placeholder="0">
                            </label>
                        </div>
                        <label class="field">
                            <span>Foto bukti barang refund</span>
                            <input class="input" id="refund-evidence" type="file" accept="image/*" multiple
                                style="padding: 10px;">
                        </label>
                        <label class="field">
                            <span>Catatan refund</span>
                            <textarea class="input" id="refund-reason" rows="3"
                                style="height:auto; min-height:84px; padding:12px 16px;"
                                placeholder="Alasan refund..."></textarea>
                        </label>
                        <div class="refund-summary" id="refund-summary"></div>
                        <button class="primary-btn" type="button" id="process-refund" disabled>
                            Proses Refund
                        </button>
                    </section>
                </div>
            </div>
        </div>
    </div>

    <div id="refund-product-modal" class="modal-backdrop hidden">
        <div class="modal" style="max-width: 760px;">
            <header class="modal-header">
                <h3>Pilih Barang Pengganti</h3>
                <button type="button" id="close-refund-product-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body refund-product-body">
                <input type="search" id="refund-product-search" class="input modal-search-input refund-product-search"
                    placeholder="Cari nama produk atau SKU...">
                <div id="refund-product-list" class="refund-product-grid"></div>
                <div class="refund-product-actions">
                    <button type="button" id="apply-refund-products" class="primary-btn receipt-print-btn">
                        Terapkan Pilihan
                    </button>
                    <button type="button" id="cancel-refund-products" class="small-btn" style="height:44px;">
                        Batal
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="customer-manual-modal" class="modal-backdrop hidden">
        <div class="modal">
            <header class="modal-header">
                <h3>Pelanggan Baru</h3>
                <button type="button" id="close-customer-manual-modal" class="close-btn">&times;</button>
            </header>
            <div class="modal-body">
                <input type="hidden" id="customer-id">
                <label class="field">
                    <span>Nama Lengkap</span>
                    <input class="input" id="customer-name" type="text" placeholder="Pelanggan Umum">
                </label>
                <label class="field">
                    <span>No. WhatsApp / HP</span>
                    <input class="input" id="customer-phone" type="tel" placeholder="Opsional">
                </label>
                <button type="button" id="btn-apply-customer" class="primary-btn" style="margin-top: 8px;">Simpan &
                    Terapkan Pelanggan</button>
            </div>
        </div>
    </div>

    <script>
        const root = document.querySelector('[data-cashier]');
        const state = {
            products: [],
            cart: new Map(),
            lastQuery: '',
            customerDuplicate: false,
            transactions: [],
            refundSales: [],
            refundSelectedSale: null,
            refundProducts: [],
            refundReplacementCart: new Map(),
            refundReplacementDraft: new Map(),
            pricing: {
                tax_percentage: 0,
                service_fee_percentage: 0,
            },
            discount: null,
        };

        const els = {
            store: document.getElementById('store'),
            search: document.getElementById('search'),
            scan: document.getElementById('scan'),
            catalog: document.getElementById('catalog'),
            cart: document.getElementById('cart'),
            itemCount: document.getElementById('item-count'),
            subtotal: document.getElementById('subtotal'),
            discountCode: document.getElementById('discount-code'),
            applyDiscount: document.getElementById('apply-discount'),
            clearDiscount: document.getElementById('clear-discount'),
            discountRowDisplay: document.getElementById('discount-row-display'),
            discountTotal: document.getElementById('discount-total'),
            serviceFeeRate: document.getElementById('service-fee-rate'),
            serviceFeeTotal: document.getElementById('service-fee-total'),
            taxRate: document.getElementById('tax-rate'),
            taxTotal: document.getElementById('tax-total'),
            grandTotal: document.getElementById('grand-total'),
            change: document.getElementById('change'),
            debtRowDisplay: document.getElementById('debt-row-display'),
            debtTotal: document.getElementById('debt-total'),
            reset: document.getElementById('reset'),
            checkout: document.getElementById('checkout'),
            customerId: document.getElementById('customer-id'),
            customerName: document.getElementById('customer-name'),
            customerPhone: document.getElementById('customer-phone'),
            paymentMethod: document.getElementById('payment-method'),
            paidAmount: document.getElementById('paid-amount'),
            paidLabel: document.getElementById('paid-label'),
            paidField: document.getElementById('paid-field'),
            isDebt: document.getElementById('is-debt'),
            proofField: document.getElementById('proof-field'),
            paymentProof: document.getElementById('payment-proof'),
            toast: document.getElementById('toast'),
        };

        const money = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        });

        const debounce = (fn, wait = 250) => {
            let timeout;

            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => fn(...args), wait);
            };
        };

        const rupiah = (value) => money.format(Number(value || 0));

        const currencyDigits = (value) => String(value ?? '').replace(/\D/g, '');

        const formatCurrencyInput = (value) => {
            const digits = currencyDigits(value);

            return digits === '' ? '' : new Intl.NumberFormat('id-ID').format(Number(digits));
        };

        const currencyValue = (input) => Number(currencyDigits(input.value) || 0);

        const bindCurrencyInput = (input, onChange) => {
            input.addEventListener('input', () => {
                input.value = formatCurrencyInput(input.value);
                onChange();
            });
        };

        const showToast = (message, type = '') => {
            els.toast.innerHTML = type === 'success'
                ? `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg> ${escapeHtml(message)}`
                : `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg> ${escapeHtml(message)}`;

            els.toast.className = `toast ${type}`.trim();
            els.toast.classList.remove('hidden');
            setTimeout(() => els.toast.classList.add('hidden'), 3200);
        };

        const productImage = (product) => product.image_url
            ? `<img src="${product.image_url}" alt="${escapeHtml(product.name)}">`
            : `<div class="product-fallback">${escapeHtml(product.name.charAt(0).toUpperCase())}</div>`;

        const escapeHtml = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const loadProducts = async () => {
            const params = new URLSearchParams({
                store_id: els.store.value,
                q: els.search.value,
            });

            const response = await fetch(`${root.dataset.productsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                showToast('Gagal memuat produk', 'error');
                return;
            }

            const data = await response.json();
            state.products = data.products;
            renderCatalog();
        };

        const renderCatalog = () => {
            if (state.products.length === 0) {
                els.catalog.innerHTML = `
                    <div class="empty" style="grid-column: 1 / -1; min-height: 300px;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:64px;height:64px;margin-bottom:16px;color:var(--line);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <p style="font-size: 16px; font-weight: 600;">Produk tidak ditemukan</p>
                        <span style="font-size: 14px; margin-top: 8px;">Coba kata kunci lain atau periksa kembali ejaan Anda.</span>
                    </div>`;
                return;
            }

            els.catalog.innerHTML = state.products.map((product) => `
                <button class="product" type="button" data-add="${product.id}" ${product.stock <= 0 ? 'disabled' : ''}>
                    <div class="product-image">${productImage(product)}</div>
                    <div class="product-body">
                        <div class="product-name">${escapeHtml(product.name)}</div>
                        <div class="product-code">${escapeHtml(product.code || '-')}</div>
                        <div class="product-foot">
                            <div>
                                <div class="price">${rupiah(product.price)}</div>
                                <div class="stock ${product.stock < 5 ? 'low' : ''}">Sisa ${product.stock} ${escapeHtml(product.unit || 'pcs')}</div>
                            </div>
                            <div class="add-pill">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            </div>
                        </div>
                    </div>
                </button>
            `).join('');
        };

        const addProduct = (productId) => {
            const product = state.products.find((item) => item.id === productId);

            if (!product) {
                showToast('Produk tidak ditemukan', 'error');
                return;
            }

            const item = state.cart.get(product.id) || { ...product, quantity: 0 };

            if (item.quantity + 1 > product.stock) {
                showToast(`Stok ${product.name} tidak cukup`, 'error');
                return;
            }

            item.quantity += 1;
            state.cart.set(product.id, item);
            renderOrder();
        };

        const changeQty = (productId, delta) => {
            const item = state.cart.get(productId);
            if (!item) return;

            const nextQty = item.quantity + delta;
            if (nextQty <= 0) {
                state.cart.delete(productId);
            } else if (nextQty <= item.stock) {
                item.quantity = nextQty;
                state.cart.set(productId, item);
            }

            renderOrder();
        };

        const subtotal = () => Array.from(state.cart.values())
            .reduce((total, item) => total + (item.price * item.quantity), 0);

        const calculateTotals = () => {
            const rawSubtotal = subtotal();
            let discountTotal = 0;

            if (state.discount) {
                const value = Number(state.discount.value || 0);
                discountTotal = state.discount.type === 'percentage'
                    ? rawSubtotal * Math.min(value, 100) / 100
                    : value;
                discountTotal = Math.min(rawSubtotal, Math.max(0, discountTotal));
            }

            const base = Math.max(0, rawSubtotal - discountTotal);
            const serviceFeeTotal = base * Number(state.pricing.service_fee_percentage || 0) / 100;
            const taxTotal = base * Number(state.pricing.tax_percentage || 0) / 100;

            return {
                subtotal: rawSubtotal,
                discountTotal,
                serviceFeeTotal,
                taxTotal,
                grandTotal: base + serviceFeeTotal + taxTotal,
            };
        };

        const loadPricing = async (discountCode = '') => {
            const params = new URLSearchParams({
                store_id: els.store.value,
                subtotal: subtotal(),
            });

            if (discountCode) {
                params.set('discount_code', discountCode);
            }

            const response = await fetch(`${root.dataset.pricingUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Kode diskon tidak valid');
            }

            state.pricing.tax_percentage = Number(data.pricing.tax_percentage || 0);
            state.pricing.service_fee_percentage = Number(data.pricing.service_fee_percentage || 0);

            if (data.pricing.discount_code) {
                state.discount = {
                    code: data.pricing.discount_code,
                    name: data.pricing.discount_name,
                    type: data.pricing.discount_type,
                    value: Number(data.pricing.discount_value || 0),
                };
            }

            renderOrder();

            return data.pricing;
        };

        const applyDiscount = async () => {
            const code = els.discountCode.value.trim().toUpperCase();

            if (!code) {
                state.discount = null;
                renderOrder();
                return;
            }

            try {
                const pricing = await loadPricing(code);
                els.discountCode.value = pricing.discount_code || code;
                showToast('Kode diskon diterapkan', 'success');
            } catch (error) {
                state.discount = null;
                showToast(error.message, 'error');
                renderOrder();
            }
        };

        const clearDiscount = (showMessage = true) => {
            state.discount = null;
            els.discountCode.value = '';
            if (showMessage) showToast('Kode diskon dihapus', 'success');
            renderOrder();
        };

        const renderOrder = () => {
            const items = Array.from(state.cart.values());

            els.itemCount.textContent = items.reduce((total, item) => total + item.quantity, 0);

            if (items.length === 0) {
                els.cart.innerHTML = `
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                        <p>Keranjang Kosong</p>
                        <span style="font-size: 13px; margin-top: 6px;">Pilih produk dari katalog untuk memulai.</span>
                    </div>`;
            } else {
                els.cart.innerHTML = items.map((item) => `
                    <div class="cart-item">
                        <div class="cart-thumb">${productImage(item)}</div>
                        <div style="min-width: 0; flex: 1;">
                            <div class="cart-title-row">
                                <div style="min-width: 0; padding-right: 8px; flex: 1;">
                                    <div class="cart-title">${escapeHtml(item.name)}</div>
                                    <div class="item-total" style="margin-top: 4px;">${rupiah(item.price * item.quantity)}</div>
                                </div>
                                <button class="icon-btn remove" type="button" data-remove="${item.id}" title="Hapus">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                            <div class="cart-control-row">
                                <div class="qty">
                                    <button class="icon-btn" type="button" data-dec="${item.id}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                    <strong>${item.quantity}</strong>
                                    <button class="icon-btn" type="button" data-inc="${item.id}">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
            }

            const totals = calculateTotals();
            const paid = els.isDebt.checked
                ? currencyValue(els.paidAmount)
                : (els.paymentMethod.value === 'qris' ? totals.grandTotal : currencyValue(els.paidAmount));
            const debt = els.isDebt.checked ? Math.max(0, totals.grandTotal - paid) : 0;
            els.subtotal.textContent = rupiah(totals.subtotal);
            els.discountTotal.textContent = `- ${rupiah(totals.discountTotal)}`;
            els.serviceFeeRate.textContent = `(${Number(state.pricing.service_fee_percentage || 0)}%)`;
            els.serviceFeeTotal.textContent = rupiah(totals.serviceFeeTotal);
            els.taxRate.textContent = `(${Number(state.pricing.tax_percentage || 0)}%)`;
            els.taxTotal.textContent = rupiah(totals.taxTotal);
            els.grandTotal.textContent = rupiah(totals.grandTotal);
            els.change.textContent = rupiah(els.isDebt.checked ? 0 : Math.max(0, paid - totals.grandTotal));
            els.debtTotal.textContent = rupiah(debt);
            els.discountRowDisplay.style.display = totals.discountTotal > 0 ? 'flex' : 'none';
            els.debtRowDisplay.style.display = els.isDebt.checked ? 'flex' : 'none';
            els.checkout.disabled = items.length === 0;
            els.paidLabel.textContent = els.isDebt.checked ? 'Nominal Sudah Dibayar' : 'Nominal Diterima';
            els.paidField.style.display = els.paymentMethod.value === 'cash' || els.isDebt.checked ? 'flex' : 'none';
            els.proofField.style.display = els.paymentMethod.value === 'qris' ? 'flex' : 'none';
        };

        const checkout = async () => {
            const hasDuplicate = await validateCustomerDuplicate(true);
            if (hasDuplicate) return;

            const totals = calculateTotals();
            const debtChecked = els.isDebt.checked;
            const hasCustomer = Boolean(els.customerId.value || els.customerName.value.trim() || els.customerPhone.value.trim());

            if (debtChecked && !hasCustomer) {
                showToast('Transaksi hutang wajib memilih atau membuat pelanggan', 'error');
                return;
            }

            const payload = new FormData();

            payload.append('store_id', els.store.value);
            if (els.customerId.value) payload.append('customer_id', els.customerId.value);
            if (els.customerName.value) payload.append('customer_name', els.customerName.value);
            if (els.customerPhone.value) payload.append('customer_phone', els.customerPhone.value);
            payload.append('payment_method', els.paymentMethod.value);
            payload.append('is_debt', debtChecked ? '1' : '0');
            payload.append('paid_amount', debtChecked
                ? currencyValue(els.paidAmount)
                : (els.paymentMethod.value === 'qris' ? totals.grandTotal : currencyValue(els.paidAmount)));
            if (state.discount?.code) payload.append('discount_code', state.discount.code);

            if (els.paymentMethod.value === 'qris' && els.paymentProof.files.length > 0) {
                payload.append('payment_proof', els.paymentProof.files[0]);
            }

            if (els.paymentMethod.value === 'qris' && els.paymentProof.files.length === 0) {
                showToast('Upload bukti transfer untuk pembayaran QRIS', 'error');
                renderOrder();
                return;
            }

            Array.from(state.cart.values()).forEach((item, index) => {
                payload.append(`items[${index}][product_id]`, item.id);
                payload.append(`items[${index}][quantity]`, item.quantity);
            });

            els.checkout.disabled = true;

            try {
                const response = await fetch(root.dataset.checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                    body: payload,
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Transaksi gagal');
                }

                showToast(`Order ${data.sale.number} selesai diproses!`, 'success');
                showReceipt(data.sale);
                state.cart.clear();
                els.customerId.value = '';
                els.customerName.value = '';
                els.customerPhone.value = '';
                els.paidAmount.value = '';
                els.paymentProof.value = '';
                els.isDebt.checked = false;
                clearDiscount(false);
                await loadProducts();
                renderOrder();
            } catch (error) {
                showToast(error.message, 'error');
                renderOrder();
            }
        };

        els.catalog.addEventListener('click', (event) => {
            const button = event.target.closest('[data-add]');
            if (!button) return;

            addProduct(Number(button.dataset.add));
        });

        els.cart.addEventListener('click', (event) => {
            const inc = event.target.closest('[data-inc]');
            const dec = event.target.closest('[data-dec]');
            const remove = event.target.closest('[data-remove]');

            if (inc) changeQty(Number(inc.dataset.inc), 1);
            if (dec) changeQty(Number(dec.dataset.dec), -1);
            if (remove) {
                state.cart.delete(Number(remove.dataset.remove));
                renderOrder();
            }
        });

        els.search.addEventListener('input', debounce(loadProducts));
        els.store.addEventListener('change', () => {
            state.cart.clear();
            els.customerId.value = '';
            els.customerName.value = '';
            els.customerPhone.value = '';
            state.customerDuplicate = false;
            state.discount = null;
            els.discountCode.value = '';
            els.isDebt.checked = false;
            renderOrder();
            loadProducts();
            loadPricing().catch(() => { });
        });
        document.querySelectorAll('.payment-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.payment-card').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                els.paymentMethod.value = card.dataset.method;
                renderOrder();
            });
        });

        bindCurrencyInput(els.paidAmount, renderOrder);
        els.isDebt.addEventListener('change', renderOrder);
        els.scan?.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;

            const code = els.scan.value.trim().toLowerCase();
            const product = state.products.find((item) =>
                String(item.barcode || '').toLowerCase() === code ||
                String(item.sku || '').toLowerCase() === code
            );

            if (product) {
                addProduct(product.id);
                els.scan.value = '';
                return;
            }

            els.search.value = code;
            loadProducts().then(() => {
                const found = state.products.find((item) =>
                    String(item.barcode || '').toLowerCase() === code ||
                    String(item.sku || '').toLowerCase() === code
                );

                if (found) addProduct(found.id);
                else showToast('Produk barcode tidak ditemukan', 'error');

                els.scan.value = '';
            });
        });
        const customerDuplicateCheck = debounce(() => validateCustomerDuplicate(true), 350);
        els.customerName.addEventListener('input', () => {
            els.customerId.value = '';
            customerDuplicateCheck();
        });
        els.customerPhone.addEventListener('input', () => {
            els.customerId.value = '';
            customerDuplicateCheck();
        });
        els.reset.addEventListener('click', () => {
            if (state.cart.size === 0) return;
            if (confirm('Kosongkan keranjang?')) {
                state.cart.clear();
                els.customerId.value = '';
                els.customerName.value = '';
                els.customerPhone.value = '';
                els.paidAmount.value = '';
                els.paymentProof.value = '';
                els.isDebt.checked = false;
                clearDiscount(false);
                renderOrder();
            }
        });
        els.checkout.addEventListener('click', checkout);
        els.applyDiscount.addEventListener('click', applyDiscount);
        els.clearDiscount.addEventListener('click', clearDiscount);
        els.discountCode.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') applyDiscount();
        });

        const customerSearch = document.getElementById('customer-search');
        const customerList = document.getElementById('customer-list');
        const customerManualModal = document.getElementById('customer-manual-modal');
        const transactionModal = document.getElementById('transaction-modal');
        const transactionSearch = document.getElementById('transaction-search');
        const transactionList = document.getElementById('transaction-list');
        const refundModal = document.getElementById('refund-modal');
        const refundProductModal = document.getElementById('refund-product-modal');
        const refundSearch = document.getElementById('refund-search');
        const refundSaleList = document.getElementById('refund-sale-list');
        const refundType = document.getElementById('refund-type');
        const refundSelectedSale = document.getElementById('refund-selected-sale');
        const refundReturnList = document.getElementById('refund-return-list');
        const refundReplacementSection = document.getElementById('refund-replacement-section');
        const openRefundProducts = document.getElementById('open-refund-products');
        const refundProductSearch = document.getElementById('refund-product-search');
        const refundProductList = document.getElementById('refund-product-list');
        const refundReplacementCart = document.getElementById('refund-replacement-cart');
        const refundAdditionalPayment = document.getElementById('refund-additional-payment');
        const refundEvidence = document.getElementById('refund-evidence');
        const refundReason = document.getElementById('refund-reason');
        const refundSummary = document.getElementById('refund-summary');
        const processRefundButton = document.getElementById('process-refund');
        const receiptModal = document.getElementById('receipt-modal');
        const receiptContent = document.getElementById('receipt-content');
        const markSalePaidButton = document.getElementById('mark-sale-paid');
        let lastReceiptSale = null;
        let customersTimer;

        document.getElementById('btn-open-manual-customer').addEventListener('click', () => {
            customerList.style.display = 'none';
            if (!els.customerName.value && customerSearch.value.trim()) {
                els.customerName.value = customerSearch.value.trim();
            }
            customerManualModal.classList.remove('hidden');
            els.customerName.focus();
        });

        document.getElementById('close-customer-manual-modal').addEventListener('click', () => {
            customerManualModal.classList.add('hidden');
        });

        document.getElementById('btn-apply-customer').addEventListener('click', async () => {
            const button = document.getElementById('btn-apply-customer');
            const name = els.customerName.value.trim();
            const phone = els.customerPhone.value.trim();

            if (!name) {
                showToast('Nama pelanggan wajib diisi', 'error');
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch(root.dataset.customersStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                    body: JSON.stringify({
                        store_id: els.store.value,
                        name,
                        phone,
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal menyimpan pelanggan');
                }

                selectCustomer(data.customer.id, data.customer.name, data.customer.phone || '');
                customerManualModal.classList.add('hidden');
                showToast('Pelanggan baru tersimpan', 'success');
                renderOrder();
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });

        // Hide customer list when clicking outside
        document.addEventListener('click', (e) => {
            if (!customerSearch.contains(e.target) && !customerList.contains(e.target)) {
                customerList.style.display = 'none';
            }
        });

        customerSearch.addEventListener('focus', () => {
            if (customerSearch.value.trim() !== '') {
                customerList.style.display = 'flex';
            } else {
                loadCustomers();
            }
        });

        const loadCustomers = async (query = '') => {
            customerList.style.display = 'flex';
            customerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Memuat...</div>';
            try {
                const params = new URLSearchParams({ store_id: els.store.value, q: query });
                const res = await fetch(`${root.dataset.customersUrl}?${params}`);
                const data = await res.json();

                if (data.customers.length === 0) {
                    customerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-light);">Tidak ada pelanggan ditemukan.</div>';
                    return;
                }

                customerList.innerHTML = data.customers.map(c => `
                    <div class="customer-item" onclick="selectCustomer(${c.id}, '${escapeHtml(c.name)}', '${escapeHtml(c.phone || '')}')">
                        <h4>${escapeHtml(c.name)}</h4>
                        ${c.phone ? `<p>${escapeHtml(c.phone)}</p>` : ''}
                    </div>
                `).join('');
            } catch (err) {
                customerList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat pelanggan.</div>';
            }
        };

        customerSearch.addEventListener('input', (e) => {
            clearTimeout(customersTimer);
            customersTimer = setTimeout(() => {
                loadCustomers(e.target.value);
            }, 300);
        });

        window.selectCustomer = (id, name, phone) => {
            els.customerId.value = id;
            els.customerName.value = name;
            els.customerPhone.value = phone;
            customerSearch.value = name;
            state.customerDuplicate = false;
            customerList.style.display = 'none';
            renderOrder();
        };

        const validateCustomerDuplicate = async (showMessage = false) => {
            if (els.customerId.value) {
                state.customerDuplicate = false;
                return false;
            }

            const name = els.customerName.value.trim();
            const phone = els.customerPhone.value.trim();

            if (!name && !phone) {
                state.customerDuplicate = false;
                return false;
            }

            const params = new URLSearchParams({
                store_id: els.store.value,
                name,
                phone,
            });

            const response = await fetch(`${root.dataset.customerCheckUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) return false;

            const data = await response.json();
            state.customerDuplicate = Boolean(data.exists);

            if (state.customerDuplicate && showMessage) {
                showToast(data.message || 'Pelanggan sudah terdaftar', 'error');
            }

            return state.customerDuplicate;
        };

        const receiptHtml = (sale) => {
            if (sale.receipt_type === 'refund') {
                return refundReceiptHtml(sale);
            }

            const items = (sale.items || []).map((item) => `
                <div class="receipt-item">
                    <div class="receipt-item-name">${escapeHtml(item.name)}</div>
                    <div class="receipt-row">
                        <span>${item.quantity} x ${rupiah(item.unit_price)}</span>
                        <strong>${rupiah(item.line_total)}</strong>
                    </div>
                </div>
            `).join('');
            const debtLine = sale.is_debt
                ? `<div class="receipt-line"><span>Hutang</span><strong>${rupiah(sale.debt_amount)}</strong></div>`
                : '';

            return `
                <div class="receipt-head">
                    <h3>${escapeHtml(sale.store_name || 'omsetaPOS')}</h3>
                    <div>${escapeHtml(sale.number)}</div>
                    <div>${escapeHtml(sale.paid_at || '')}</div>
                </div>
                <div class="receipt-line"><span>Kasir</span><strong>${escapeHtml(sale.cashier_name || '-')}</strong></div>
                <div class="receipt-line"><span>Pelanggan</span><strong>${escapeHtml(sale.customer_name || 'Pelanggan Umum')}</strong></div>
                <div class="receipt-line"><span>Status</span><strong>${escapeHtml(sale.payment_status_label || 'Lunas')}</strong></div>
                <div class="receipt-items">${items}</div>
                <div class="receipt-line"><span>Subtotal</span><strong>${rupiah(sale.subtotal)}</strong></div>
                <div class="receipt-line"><span>Diskon</span><strong>- ${rupiah(sale.discount_total)}</strong></div>
                <div class="receipt-line"><span>Service fee</span><strong>${rupiah(sale.service_fee_total)}</strong></div>
                <div class="receipt-line"><span>Tax</span><strong>${rupiah(sale.tax_total)}</strong></div>
                <div class="receipt-line receipt-total"><span>Total</span><strong>${rupiah(sale.grand_total)}</strong></div>
                <div class="receipt-line"><span>Bayar</span><strong>${rupiah(sale.paid_amount)}</strong></div>
                <div class="receipt-line"><span>Kembali</span><strong>${rupiah(sale.change_amount)}</strong></div>
                ${debtLine}
                <div class="receipt-head" style="border-bottom:0;border-top:1px dashed #94a3b8;padding:10px 0 0;margin:10px 0 0;">
                    Terima kasih
                </div>
            `;
        };

        const refundReceiptHtml = (refund) => {
            const returnedItems = (refund.items || [])
                .filter((item) => item.direction === 'returned')
                .map((item) => `
                    <div class="receipt-item">
                        <div class="receipt-item-name">${escapeHtml(item.name)}</div>
                        <div class="receipt-row">
                            <span>${item.quantity} x ${rupiah(item.unit_price)}</span>
                            <strong>${rupiah(item.line_total)}</strong>
                        </div>
                    </div>
                `).join('');
            const replacementItems = (refund.items || [])
                .filter((item) => item.direction === 'replacement')
                .map((item) => `
                    <div class="receipt-item">
                        <div class="receipt-item-name">${escapeHtml(item.name)}</div>
                        <div class="receipt-row">
                            <span>${item.quantity} x ${rupiah(item.unit_price)}</span>
                            <strong>${rupiah(item.line_total)}</strong>
                        </div>
                    </div>
                `).join('');

            return `
                <div class="receipt-head">
                    <h3>${escapeHtml(refund.store_name || 'omsetaPOS')}</h3>
                    <div>${escapeHtml(refund.number)}</div>
                    <div>Refund dari ${escapeHtml(refund.sale_number || '-')}</div>
                    <div>${escapeHtml(refund.created_at || '')}</div>
                </div>
                <div class="receipt-line"><span>Diproses</span><strong>${escapeHtml(refund.handled_by_name || '-')}</strong></div>
                <div class="receipt-line"><span>Pelanggan</span><strong>${escapeHtml(refund.customer_name || 'Pelanggan Umum')}</strong></div>
                <div class="receipt-line"><span>Tipe</span><strong>${escapeHtml(refund.type === 'exchange' ? 'Ganti barang' : 'Full refund')}</strong></div>
                <div class="receipt-items">
                    <div class="receipt-item-name">Barang dikembalikan</div>
                    ${returnedItems || '<div class="transaction-meta">Tidak ada item</div>'}
                </div>
                ${replacementItems ? `
                    <div class="receipt-items">
                        <div class="receipt-item-name">Barang pengganti</div>
                        ${replacementItems}
                    </div>
                ` : ''}
                <div class="receipt-line"><span>Nilai retur</span><strong>${rupiah(refund.returned_total)}</strong></div>
                <div class="receipt-line"><span>Barang pengganti</span><strong>${rupiah(refund.replacement_total)}</strong></div>
                <div class="receipt-line receipt-total"><span>Uang kembali</span><strong>${rupiah(refund.refund_amount)}</strong></div>
                <div class="receipt-line"><span>Tambahan wajib</span><strong>${rupiah(refund.additional_payment_amount)}</strong></div>
                <div class="receipt-line"><span>Tambahan diterima</span><strong>${rupiah(refund.additional_paid_amount)}</strong></div>
                <div class="receipt-line"><span>Kembalian tambahan</span><strong>${rupiah(refund.change_amount)}</strong></div>
                ${refund.reason ? `<div class="receipt-line"><span>Catatan</span><strong>${escapeHtml(refund.reason)}</strong></div>` : ''}
                <div class="receipt-head" style="border-bottom:0;border-top:1px dashed #94a3b8;padding:10px 0 0;margin:10px 0 0;">
                    Refund selesai
                </div>
            `;
        };

        const showReceipt = (sale) => {
            lastReceiptSale = sale;
            receiptContent.innerHTML = receiptHtml(sale);
            markSalePaidButton.style.display = sale.receipt_type !== 'refund' && sale.payment_status === 'belum_lunas' ? 'block' : 'none';
            markSalePaidButton.disabled = false;
            receiptModal.classList.remove('hidden');
        };

        const printReceipt = () => {
            if (!lastReceiptSale) return;

            const printWindow = window.open('', '_blank', 'width=420,height=640');
            if (!printWindow) {
                showToast('Popup print diblokir browser', 'error');
                return;
            }

            printWindow.document.write(`
                <!doctype html>
                <html>
                <head>
                    <title>${escapeHtml(lastReceiptSale.number)}</title>
                    <style>
                        @page { size: 80mm auto; margin: 4mm; }
                        * { box-sizing: border-box; }
                        body { margin: 0; color: #111; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
                        .receipt { width: 72mm; margin: 0 auto; font-size: 11px; }
                        .receipt-head { text-align: center; border-bottom: 1px dashed #111; padding-bottom: 8px; margin-bottom: 8px; }
                        .receipt-head h3 { font-size: 15px; margin: 0 0 4px; }
                        .receipt-line, .receipt-row { display: flex; justify-content: space-between; gap: 8px; line-height: 1.45; }
                        .receipt-items { border-top: 1px dashed #111; border-bottom: 1px dashed #111; margin: 8px 0; padding: 6px 0; }
                        .receipt-item { margin-bottom: 6px; }
                        .receipt-item-name { font-weight: 700; line-height: 1.35; }
                        .receipt-total { font-weight: 800; font-size: 13px; }
                    </style>
                </head>
                <body>
                    <div class="receipt">${receiptHtml(lastReceiptSale)}</div>
                    <script>
                        window.addEventListener('load', () => {
                            window.print();
                            setTimeout(() => window.close(), 500);
                        });
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        };

        const transactionStatus = (sale) => {
            if (['refunded', 'partially_refunded'].includes(sale.status)) {
                return { label: 'refund', className: 'refund' };
            }

            if (sale.payment_status === 'belum_lunas') {
                return { label: 'belum lunas', className: 'debt' };
            }

            return { label: sale.payment_status_label || 'lunas', className: '' };
        };

        const renderTransactions = (transactions) => {
            state.transactions = transactions;

            if (transactions.length === 0) {
                transactionList.innerHTML = '<div style="text-align:center; padding:24px; color:var(--muted);">Riwayat transaksi tidak ditemukan.</div>';
                return;
            }

            transactionList.innerHTML = transactions.map((sale) => {
                const itemText = sale.items
                    .map((item) => `${escapeHtml(item.name)} x${item.quantity}`)
                    .join(', ');
                const debtText = sale.is_debt ? ` · Hutang ${rupiah(sale.debt_amount)}` : '';
                const status = transactionStatus(sale);

                return `
                    <div class="transaction-item" data-sale-id="${sale.id}" role="button" tabindex="0" title="Klik untuk membuka detail nota">
                        <div class="transaction-top">
                            <div>
                                <div class="transaction-number">${escapeHtml(sale.number)}</div>
                                <div class="transaction-meta">${escapeHtml(sale.customer_name)} · ${escapeHtml(sale.payment_method)}${debtText} · ${escapeHtml(sale.paid_at)}</div>
                                <div class="transaction-status ${status.className}">${status.label}</div>
                            </div>
                            <div class="transaction-total">${rupiah(sale.grand_total)}</div>
                        </div>
                        <div class="transaction-items">${itemText}</div>
                    </div>
                `;
            }).join('');
        };

        const loadTransactions = async () => {
            transactionList.innerHTML = '<div style="text-align:center; padding:24px; color:var(--muted);">Memuat...</div>';

            const params = new URLSearchParams({
                store_id: els.store.value,
                q: transactionSearch.value,
            });

            const response = await fetch(`${root.dataset.transactionsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                transactionList.innerHTML = '<div style="text-align:center; padding:24px; color:var(--danger);">Gagal memuat riwayat transaksi.</div>';
                return;
            }

            const data = await response.json();
            renderTransactions(data.transactions || []);
        };

        const resetRefundForm = () => {
            state.refundSales = [];
            state.refundSelectedSale = null;
            state.refundProducts = [];
            state.refundReplacementCart.clear();
            state.refundReplacementDraft.clear();
            refundSearch.value = '';
            refundType.value = 'full';
            refundProductSearch.value = '';
            refundAdditionalPayment.value = '';
            refundEvidence.value = '';
            refundReason.value = '';
            refundSaleList.innerHTML = '';
            refundProductList.innerHTML = '';
            refundReplacementCart.innerHTML = '';
            refundReturnList.innerHTML = '';
            refundSelectedSale.innerHTML = 'Pilih transaksi dulu.';
            refundSummary.innerHTML = '<div class="summary-line"><span>Retur</span><strong>Rp 0</strong></div>';
            processRefundButton.disabled = true;
            refundReplacementSection.style.display = 'none';
        };

        const refundableItems = (sale) => (sale?.items || [])
            .filter((item) => Number(item.refundable_quantity || 0) > 0);

        const renderRefundSales = (sales) => {
            state.refundSales = sales;

            if (sales.length === 0) {
                refundSaleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--muted);">Transaksi tidak ditemukan.</div>';
                return;
            }

            refundSaleList.innerHTML = sales.map((sale) => {
                const status = transactionStatus(sale);
                const active = state.refundSelectedSale && Number(state.refundSelectedSale.id) === Number(sale.id);
                const disabled = sale.payment_status === 'belum_lunas';

                return `
                    <div class="refund-sale ${active ? 'active' : ''} ${disabled ? 'disabled' : ''}" data-refund-sale-id="${sale.id}" data-refund-disabled="${disabled ? '1' : '0'}">
                        <div class="transaction-top" style="margin-bottom:4px;">
                            <div>
                                <div class="transaction-number">${escapeHtml(sale.number)}</div>
                                <div class="transaction-meta">${escapeHtml(sale.customer_name)} · ${escapeHtml(sale.paid_at)}</div>
                                <div class="transaction-status ${status.className}">${status.label}</div>
                            </div>
                            <div class="transaction-total">${rupiah(sale.grand_total)}</div>
                        </div>
                        ${disabled ? '<div class="transaction-meta" style="color:#92400e;font-weight:700;">Lunasi transaksi sebelum refund.</div>' : ''}
                    </div>
                `;
            }).join('');
        };

        const loadRefundSales = async () => {
            refundSaleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--muted);">Memuat...</div>';

            const params = new URLSearchParams({
                store_id: els.store.value,
                q: refundSearch.value,
            });

            const response = await fetch(`${root.dataset.transactionsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                refundSaleList.innerHTML = '<div style="text-align:center; padding:20px; color:var(--danger);">Gagal memuat transaksi.</div>';
                return;
            }

            const data = await response.json();
            renderRefundSales(data.transactions || []);
        };

        const selectedReturnRows = () => {
            const sale = state.refundSelectedSale;
            if (!sale) return [];

            if (refundType.value === 'full') {
                return refundableItems(sale).map((item) => ({
                    sale_item_id: item.id,
                    quantity: Number(item.refundable_quantity || 0),
                    unit_price: Number(item.unit_price || 0),
                }));
            }

            return Array.from(refundReturnList.querySelectorAll('[data-refund-return-id]'))
                .map((input) => {
                    const saleItem = sale.items.find((item) => Number(item.id) === Number(input.dataset.refundReturnId));

                    return {
                        sale_item_id: Number(input.dataset.refundReturnId),
                        quantity: Math.max(0, Number(input.value || 0)),
                        unit_price: Number(saleItem?.unit_price || 0),
                    };
                })
                .filter((item) => item.quantity > 0);
        };

        const refundTotals = () => {
            const returnedTotal = selectedReturnRows()
                .reduce((total, item) => total + (item.quantity * item.unit_price), 0);
            const replacementTotal = Array.from(state.refundReplacementCart.values())
                .reduce((total, item) => total + (item.quantity * item.price), 0);

            return {
                returnedTotal,
                replacementTotal,
                refundAmount: Math.max(0, returnedTotal - replacementTotal),
                additionalPayment: Math.max(0, replacementTotal - returnedTotal),
                additionalPaid: currencyValue(refundAdditionalPayment),
                additionalChange: Math.max(0, currencyValue(refundAdditionalPayment) - Math.max(0, replacementTotal - returnedTotal)),
            };
        };

        const renderRefundSummary = () => {
            const totals = refundTotals();
            const needsReplacement = refundType.value === 'exchange';
            const hasReplacement = state.refundReplacementCart.size > 0;
            const hasEvidence = refundEvidence.files.length > 0;
            const additionalPaid = currencyValue(refundAdditionalPayment);
            const additionalOk = additionalPaid >= totals.additionalPayment;
            const canProcess = Boolean(state.refundSelectedSale)
                && selectedReturnRows().length > 0
                && hasEvidence
                && (!needsReplacement || hasReplacement)
                && (!needsReplacement || additionalOk);

            refundSummary.innerHTML = `
                <div class="summary-line"><span>Nilai retur</span><strong>${rupiah(totals.returnedTotal)}</strong></div>
                <div class="summary-line"><span>Barang pengganti</span><strong>${rupiah(totals.replacementTotal)}</strong></div>
                <div class="summary-line"><span>Uang kembali</span><strong>${rupiah(totals.refundAmount + totals.additionalChange)}</strong></div>
                <div class="summary-line"><span>Tambahan wajib</span><strong>${rupiah(totals.additionalPayment)}</strong></div>
                <div class="summary-line"><span>Tambahan diterima</span><strong>${rupiah(totals.additionalPaid)}</strong></div>
            `;
            processRefundButton.disabled = !canProcess;
        };

        const renderRefundDetails = () => {
            const sale = state.refundSelectedSale;
            const isExchange = refundType.value === 'exchange';
            refundReplacementSection.style.display = isExchange ? 'flex' : 'none';

            if (!sale) {
                refundSelectedSale.innerHTML = 'Pilih transaksi dulu.';
                refundReturnList.innerHTML = '';
                renderRefundSummary();
                return;
            }

            const items = refundableItems(sale);
            const status = transactionStatus(sale);

            refundSelectedSale.innerHTML = `
                <div style="text-align:left;">
                    <div class="transaction-number">${escapeHtml(sale.number)}</div>
                    <div class="transaction-meta">${escapeHtml(sale.customer_name)} · ${escapeHtml(sale.paid_at)}</div>
                    <div class="transaction-status ${status.className}">${status.label}</div>
                </div>
            `;

            if (items.length === 0) {
                refundReturnList.innerHTML = '<div style="text-align:center; padding:16px; color:var(--muted);">Semua item transaksi ini sudah direfund.</div>';
                renderRefundSummary();
                return;
            }

            refundReturnList.innerHTML = items.map((item) => `
                <div class="refund-line">
                    <div>
                        <div class="transaction-number">${escapeHtml(item.name)}</div>
                        <div class="transaction-meta">Dibeli ${item.quantity} · Sisa refund ${item.refundable_quantity} · ${rupiah(item.unit_price)}</div>
                    </div>
                    <input type="number" min="0" max="${item.refundable_quantity}" value="${isExchange ? Math.min(1, Number(item.refundable_quantity)) : item.refundable_quantity}"
                        data-refund-return-id="${item.id}" ${isExchange ? '' : 'disabled'}>
                </div>
            `).join('');

            renderRefundSummary();
        };

        const loadRefundProducts = async () => {
            if (refundType.value !== 'exchange') return;

            const params = new URLSearchParams({
                store_id: els.store.value,
                q: refundProductSearch.value,
            });

            const response = await fetch(`${root.dataset.productsUrl}?${params}`, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                refundProductList.innerHTML = '<div style="text-align:center; padding:16px; color:var(--danger);">Gagal memuat produk.</div>';
                return;
            }

            const data = await response.json();
            state.refundProducts = data.products || [];
            renderRefundProducts();
        };

        const cloneReplacementCart = () => new Map(
            Array.from(state.refundReplacementCart.entries()).map(([id, item]) => [id, { ...item }])
        );

        const openReplacementPicker = async () => {
            if (refundType.value !== 'exchange') return;

            state.refundReplacementDraft = cloneReplacementCart();
            refundProductSearch.value = '';
            refundProductModal.classList.remove('hidden');
            refundProductSearch.focus();
            await loadRefundProducts();
        };

        const renderRefundProducts = () => {
            if (state.refundProducts.length === 0) {
                refundProductList.innerHTML = '<div style="text-align:center; padding:32px; color:var(--muted); grid-column:1/-1;"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="width:48px;height:48px;margin:0 auto 16px;color:var(--line);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg><p style="font-weight:500;font-size:16px;">Produk tidak ditemukan</p></div>';
                return;
            }

            refundProductList.innerHTML = state.refundProducts.slice(0, 20).map((product) => {
                const selected = state.refundReplacementDraft.get(product.id);
                const quantity = selected?.quantity || 1;

                return `
                <label class="refund-product-card">
                    <input type="checkbox" data-refund-draft-check="${product.id}" ${selected ? 'checked' : ''}>
                    <div class="cart-thumb">
                        ${productImage(product)}
                    </div>
                    <div class="refund-product-card-content">
                        <div class="refund-product-card-title">${escapeHtml(product.name)}</div>
                        <div class="refund-product-card-meta">
                            <span>${escapeHtml(product.code || '-')}</span>
                            <span>&bull;</span>
                            <span style="color: ${product.stock > 0 ? 'var(--text)' : 'var(--danger)'}">Stok ${product.stock}</span>
                            <span>&bull;</span>
                            <span class="refund-product-card-price">${rupiah(product.price)}</span>
                        </div>
                        <div class="refund-product-card-qty">
                            <span>Kuantitas:</span>
                            <input type="number" min="1" max="${product.stock}" value="${quantity}" data-refund-draft-qty="${product.id}"
                                class="input" ${selected ? '' : 'disabled'}>
                        </div>
                    </div>
                </label>
                `;
            }).join('');
        };

        const setRefundDraftProduct = (productId, checked) => {
            const product = state.refundProducts.find((item) => Number(item.id) === Number(productId))
                || state.refundReplacementCart.get(productId);
            if (!product) return;

            if (!checked) {
                state.refundReplacementDraft.delete(product.id);
                renderRefundProducts();
                return;
            }

            const current = state.refundReplacementDraft.get(product.id);
            state.refundReplacementDraft.set(product.id, {
                ...product,
                quantity: current?.quantity || 1,
            });
            renderRefundProducts();
        };

        const changeRefundDraftQuantity = (productId, quantity) => {
            const item = state.refundReplacementDraft.get(productId);
            if (!item) return;

            item.quantity = Math.min(item.stock, Math.max(1, Number(quantity || 1)));
            state.refundReplacementDraft.set(productId, item);
            renderRefundProducts();
        };

        const applyRefundProductSelection = () => {
            state.refundReplacementCart = new Map(
                Array.from(state.refundReplacementDraft.entries()).map(([id, item]) => [id, { ...item }])
            );
            refundProductModal.classList.add('hidden');
            renderRefundReplacementCart();
        };

        const renderRefundReplacementCart = () => {
            const items = Array.from(state.refundReplacementCart.values());

            if (items.length === 0) {
                refundReplacementCart.innerHTML = '<div style="text-align:center; padding:12px; color:var(--muted); border:1px dashed var(--line); border-radius:var(--radius-md);">Belum ada barang pengganti.</div>';
                renderRefundSummary();
                return;
            }

            refundReplacementCart.innerHTML = items.map((item) => `
                <div class="refund-line">
                    <div>
                        <div class="transaction-number">${escapeHtml(item.name)}</div>
                        <div class="transaction-meta">${rupiah(item.price)} · Stok ${item.stock}</div>
                    </div>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <button class="icon-btn" type="button" data-refund-replacement-dec="${item.id}">-</button>
                        <strong>${item.quantity}</strong>
                        <button class="icon-btn" type="button" data-refund-replacement-inc="${item.id}">+</button>
                    </div>
                </div>
            `).join('');
            renderRefundSummary();
        };

        const changeRefundReplacementQty = (productId, delta) => {
            const item = state.refundReplacementCart.get(productId);
            if (!item) return;

            const nextQuantity = item.quantity + delta;
            if (nextQuantity <= 0) {
                state.refundReplacementCart.delete(productId);
            } else if (nextQuantity <= item.stock) {
                item.quantity = nextQuantity;
                state.refundReplacementCart.set(productId, item);
            }

            renderRefundReplacementCart();
        };

        const processRefund = async () => {
            const sale = state.refundSelectedSale;
            if (!sale) {
                showToast('Pilih transaksi yang ingin direfund', 'error');
                return;
            }

            if (refundEvidence.files.length === 0) {
                showToast('Upload minimal 1 foto bukti refund', 'error');
                return;
            }

            const totals = refundTotals();
            if (refundType.value === 'exchange' && currencyValue(refundAdditionalPayment) < totals.additionalPayment) {
                showToast('Tambahan pembayaran barang pengganti masih kurang', 'error');
                return;
            }

            const payload = new FormData();
            payload.append('store_id', els.store.value);
            payload.append('sale_id', sale.id);
            payload.append('type', refundType.value);
            payload.append('reason', refundReason.value);
            payload.append('additional_payment_amount', currencyValue(refundAdditionalPayment));

            Array.from(refundEvidence.files).forEach((file) => {
                payload.append('evidence_photos[]', file);
            });

            selectedReturnRows().forEach((item, index) => {
                payload.append(`returned_items[${index}][sale_item_id]`, item.sale_item_id);
                payload.append(`returned_items[${index}][quantity]`, item.quantity);
            });

            Array.from(state.refundReplacementCart.values()).forEach((item, index) => {
                payload.append(`replacement_items[${index}][product_id]`, item.id);
                payload.append(`replacement_items[${index}][quantity]`, item.quantity);
            });

            processRefundButton.disabled = true;

            try {
                const response = await fetch(root.dataset.refundsUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                    body: payload,
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Refund gagal diproses');
                }

                showToast(`Refund ${data.refund.number} selesai diproses`, 'success');
                refundModal.classList.add('hidden');
                showReceipt(data.refund);
                resetRefundForm();
                await loadProducts();
                if (!transactionModal.classList.contains('hidden')) {
                    loadTransactions();
                }
            } catch (error) {
                showToast(error.message, 'error');
                renderRefundSummary();
            }
        };

        const markTransactionPaid = async () => {
            if (!lastReceiptSale || lastReceiptSale.receipt_type === 'refund') return;
            if (lastReceiptSale.payment_status !== 'belum_lunas') return;

            markSalePaidButton.disabled = true;

            try {
                const url = root.dataset.markPaidUrl.replace('/0/', `/${lastReceiptSale.id}/`);
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': root.dataset.csrf,
                    },
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Gagal set transaksi lunas');
                }

                lastReceiptSale = data.sale;
                state.transactions = state.transactions.map((sale) => Number(sale.id) === Number(data.sale.id) ? data.sale : sale);
                receiptContent.innerHTML = receiptHtml(data.sale);
                markSalePaidButton.style.display = 'none';
                showToast(`Transaksi ${data.sale.number} sudah lunas`, 'success');

                if (!transactionModal.classList.contains('hidden')) {
                    renderTransactions(state.transactions);
                }
            } catch (error) {
                showToast(error.message, 'error');
                markSalePaidButton.disabled = false;
            }
        };

        const openTransactionReceipt = (target) => {
            const item = target.closest('[data-sale-id]');
            if (!item) return;

            const sale = state.transactions.find((row) => Number(row.id) === Number(item.dataset.saleId));
            if (!sale) return;

            transactionModal.classList.add('hidden');
            showReceipt(sale);
        };

        document.getElementById('btn-open-transactions').addEventListener('click', () => {
            transactionModal.classList.remove('hidden');
            transactionSearch.value = '';
            transactionSearch.focus();
            loadTransactions();
        });

        document.getElementById('btn-open-refund').addEventListener('click', () => {
            resetRefundForm();
            refundModal.classList.remove('hidden');
            refundSearch.focus();
            loadRefundSales();
        });

        document.getElementById('close-transaction-modal').addEventListener('click', () => {
            transactionModal.classList.add('hidden');
        });

        document.getElementById('close-refund-modal').addEventListener('click', () => {
            refundModal.classList.add('hidden');
        });

        document.getElementById('close-refund-product-modal').addEventListener('click', () => {
            refundProductModal.classList.add('hidden');
        });

        document.getElementById('cancel-refund-products').addEventListener('click', () => {
            refundProductModal.classList.add('hidden');
        });

        document.getElementById('apply-refund-products').addEventListener('click', applyRefundProductSelection);

        transactionSearch.addEventListener('input', debounce(loadTransactions, 300));
        refundSearch.addEventListener('input', debounce(loadRefundSales, 300));
        refundType.addEventListener('change', () => {
            state.refundReplacementCart.clear();
            state.refundReplacementDraft.clear();
            refundAdditionalPayment.value = '';
            renderRefundDetails();
            renderRefundReplacementCart();
        });
        refundReturnList.addEventListener('input', renderRefundSummary);
        refundEvidence.addEventListener('change', renderRefundSummary);
        bindCurrencyInput(refundAdditionalPayment, renderRefundSummary);
        refundProductSearch.addEventListener('input', debounce(loadRefundProducts, 300));
        openRefundProducts.addEventListener('click', openReplacementPicker);
        refundSaleList.addEventListener('click', (event) => {
            const item = event.target.closest('[data-refund-sale-id]');
            if (!item) return;
            if (item.dataset.refundDisabled === '1') {
                showToast('Transaksi belum lunas tidak bisa direfund', 'error');
                return;
            }

            state.refundSelectedSale = state.refundSales.find((sale) => Number(sale.id) === Number(item.dataset.refundSaleId));
            state.refundReplacementCart.clear();
            state.refundReplacementDraft.clear();
            refundAdditionalPayment.value = '';
            renderRefundSales(state.refundSales);
            renderRefundDetails();
            renderRefundReplacementCart();
        });
        refundProductList.addEventListener('change', (event) => {
            const check = event.target.closest('[data-refund-draft-check]');
            const qty = event.target.closest('[data-refund-draft-qty]');

            if (check) setRefundDraftProduct(Number(check.dataset.refundDraftCheck), check.checked);
            if (qty) changeRefundDraftQuantity(Number(qty.dataset.refundDraftQty), qty.value);
        });
        refundProductList.addEventListener('input', (event) => {
            const qty = event.target.closest('[data-refund-draft-qty]');
            if (qty) changeRefundDraftQuantity(Number(qty.dataset.refundDraftQty), qty.value);
        });
        refundReplacementCart.addEventListener('click', (event) => {
            const inc = event.target.closest('[data-refund-replacement-inc]');
            const dec = event.target.closest('[data-refund-replacement-dec]');

            if (inc) changeRefundReplacementQty(Number(inc.dataset.refundReplacementInc), 1);
            if (dec) changeRefundReplacementQty(Number(dec.dataset.refundReplacementDec), -1);
        });
        processRefundButton.addEventListener('click', processRefund);
        transactionList.addEventListener('click', (event) => {
            openTransactionReceipt(event.target);
        });
        transactionList.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            openTransactionReceipt(event.target);
        });
        document.getElementById('close-receipt-modal').addEventListener('click', () => {
            receiptModal.classList.add('hidden');
        });
        document.getElementById('finish-receipt').addEventListener('click', () => {
            receiptModal.classList.add('hidden');
        });
        document.getElementById('print-receipt').addEventListener('click', printReceipt);
        markSalePaidButton.addEventListener('click', markTransactionPaid);

        loadPricing().catch(() => { });
        loadProducts();
        renderOrder();
    </script>
</body>

</html>
