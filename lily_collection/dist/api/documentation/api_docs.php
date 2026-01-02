<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lily Collection API Documentation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f7fa;
            color: #2d3748;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #3d5a80 0%, #2c4258 100%);
            color: white;
            padding: 30px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 2em;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .header p {
            font-size: 1em;
            opacity: 0.95;
        }

        .main-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section {
            margin-bottom: 40px;
        }

        .section h2 {
            font-size: 1.6em;
            color: #1a202c;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }

        .section h3 {
            font-size: 1.2em;
            color: #2d3748;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        .info-box {
            background: #ebf8ff;
            border-left: 4px solid #4299e1;
            padding: 12px 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .info-box.warning {
            background: #fffaf0;
            border-left-color: #ed8936;
        }

        .info-box.success {
            background: #f0fff4;
            border-left-color: #48bb78;
        }

        .code-block {
            background: #1a202c;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            overflow-x: auto;
        }

        .code-block pre {
            margin: 0;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            line-height: 1.5;
        }

        .endpoint-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
        }

        .endpoint-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .method-badge {
            background: #48bb78;
            color: white;
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.85em;
        }

        .endpoint-url {
            font-family: monospace;
            font-size: 0.95em;
            color: #2d3748;
            font-weight: 500;
        }

        .params-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 0.9em;
        }

        .params-table th {
            background: #edf2f7;
            padding: 10px;
            text-align: left;
            font-weight: 600;
            color: #2d3748;
            border-bottom: 2px solid #cbd5e0;
        }

        .params-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .required-badge {
            background: #fc8181;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7em;
            font-weight: 600;
        }

        .optional-badge {
            background: #cbd5e0;
            color: #4a5568;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7em;
            font-weight: 600;
        }

        .type-badge {
            background: #667eea;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.75em;
            font-family: monospace;
        }

        ul {
            margin-left: 20px;
            margin-top: 10px;
        }

        li {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1> Lily Collection API</h1>
            <p>Comprehensive RESTful API documentation designed to seamlessly integrate third-party applications with the Lily Collection Order Management System, enabling automated order processing, real-time inventory synchronization, and streamlined e-commerce operations.</p>
        </div>

        <main class="main-content">
            <!-- Getting Started -->
            <section class="section">
                <h2>Getting Started</h2>
                <p>This RESTful API allows you to programmatically create and manage orders in the Lily Collection OMS.</p>
                
                <div class="info-box">
                    <strong>Base URL:</strong><br>
                    <code>/lily_collection/dist/api/v1/webhook_create_order.php</code>
                </div>

                <h3>Key Features</h3>
                <ul>
                    <li>Create orders with multiple products</li>
                    <li>Automatic customer matching and creation</li>
                    <li>Real-time product validation</li>
                    <li>Flexible delivery fee calculation</li>
                </ul>
            </section>

            <!-- Authentication -->
            <section class="section">
                <h2>Authentication</h2>
                <p>All API requests require authentication using an API key in the request headers.</p>

                <h3>Header Format</h3>
                <div class="code-block">
                    <pre>Authorization: Bearer YOUR_API_KEY_HERE</pre>
                </div>

                <div class="info-box warning">
                    <strong>⚠️ Security Note:</strong> Never expose your API key in client-side code or public repositories.
                </div>
            </section>

            <!-- Create Order Endpoint -->
            <section class="section">
                <h2>Create Order</h2>
                <p>Create a new order in the system with customer details, products, and delivery information.</p>

                <div class="endpoint-card">
                    <div class="endpoint-header">
                        <span class="method-badge">POST</span>
                        <span class="endpoint-url">/webhook_create_order.php</span>
                    </div>
                    <p>Creates a new order with automatic customer matching, product validation, and delivery fee calculation.</p>
                </div>

                <h3>Request Parameters</h3>
                <table class="params-table">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>customer_name</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="required-badge">Required</span></td>
                            <td>Full name of the customer</td>
                        </tr>
                        <tr>
                            <td><code>customer_phone</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="required-badge">Required</span></td>
                            <td>Primary phone number (10 digits)</td>
                        </tr>
                        <tr>
                            <td><code>customer_phone_2</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Secondary phone number</td>
                        </tr>
                        <tr>
                            <td><code>customer_email</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Customer email address</td>
                        </tr>
                        <tr>
                            <td><code>address_line1</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="required-badge">Required</span></td>
                            <td>Primary delivery address</td>
                        </tr>
                        <tr>
                            <td><code>address_line2</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Additional address details</td>
                        </tr>
                        <tr>
                            <td><code>city_name</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="required-badge">Required*</span></td>
                            <td>City name (use this OR city_id)</td>
                        </tr>
                        <tr>
                            <td><code>city_id</code></td>
                            <td><span class="type-badge">integer</span></td>
                            <td><span class="required-badge">Required*</span></td>
                            <td>City ID from database (use this OR city_name)</td>
                        </tr>
                        <tr>
                            <td><code>products</code></td>
                            <td><span class="type-badge">array</span></td>
                            <td><span class="required-badge">Required</span></td>
                            <td>Array of product objects (see below)</td>
                        </tr>
                        <tr>
                            <td><code>order_date</code></td>
                            <td><span class="type-badge">date</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Order date (YYYY-MM-DD, defaults to today)</td>
                        </tr>
                        <tr>
                            <td><code>due_date</code></td>
                            <td><span class="type-badge">date</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Due date (defaults to +30 days)</td>
                        </tr>
                        <tr>
                            <td><code>notes</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Order notes or comments</td>
                        </tr>
                        <tr>
                            <td><code>order_status</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>"Paid" or "Unpaid" (defaults to "Unpaid")</td>
                        </tr>
                    </tbody>
                </table>

                <h3>Product Object Structure</h3>
                <table class="params-table">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>product_code</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="required-badge">Required</span></td>
                            <td>Product code from your catalog</td>
                        </tr>
                        <tr>
                            <td><code>price</code></td>
                            <td><span class="type-badge">number</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Unit price (defaults to catalog price)</td>
                        </tr>
                        <tr>
                            <td><code>discount</code></td>
                            <td><span class="type-badge">number</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Discount amount (defaults to 0)</td>
                        </tr>
                        <tr>
                            <td><code>description</code></td>
                            <td><span class="type-badge">string</span></td>
                            <td><span class="optional-badge">Optional</span></td>
                            <td>Custom product description</td>
                        </tr>
                    </tbody>
                </table>

                <div class="info-box success">
                    <strong>💡 Delivery Fee Logic:</strong><br>
                    • Orders with subtotal ≥ Rs.5,000 receive FREE delivery<br>
                    • Orders below Rs.5,000 are charged the standard delivery fee
                </div>
            </section>

            <!-- Response Codes -->
            <section class="section">
                <h2>HTTP Response Codes</h2>
                <table class="params-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>200</strong></td>
                            <td>OK</td>
                            <td>Request successful (OPTIONS preflight)</td>
                        </tr>
                        <tr>
                            <td><strong>201</strong></td>
                            <td>Created</td>
                            <td>Order created successfully</td>
                        </tr>
                        <tr>
                            <td><strong>400</strong></td>
                            <td>Bad Request</td>
                            <td>Invalid input or validation errors</td>
                        </tr>
                        <tr>
                            <td><strong>401</strong></td>
                            <td>Unauthorized</td>
                            <td>Invalid or missing API key</td>
                        </tr>
                        <tr>
                            <td><strong>500</strong></td>
                            <td>Server Error</td>
                            <td>Internal server error</td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>