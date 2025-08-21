const express = require("express");
const axios = require("axios");
const cors = require("cors");
const helmet = require("helmet");
require("dotenv").config();

const app = express();
const PORT = process.env.PORT || 3001;

// Middleware
app.use(helmet());
app.use(cors());
app.use(express.json());

// UniPayment configuration
const UNIPAYMENT_CONFIG = {
  sandbox: {
    baseUrl: "https://sandbox-api.unipayment.io",
    clientId: "5ce7507b-5afc-4c14-a8dc-b1a28a9ac99a",
    clientSecret: "9JFg5ZZSbry8yx6y54DHKWKRZhRZirAep",
  },
  production: {
    baseUrl: "https://api.unipayment.io",
    clientId: process.env.UNIPAYMENT_CLIENT_ID,
    clientSecret: process.env.UNIPAYMENT_CLIENT_SECRET,
  },
};

// Get access token
async function getAccessToken(environment = "sandbox") {
  const config = UNIPAYMENT_CONFIG[environment];

  try {
    const response = await axios.post(
      `${config.baseUrl}/connect/token`,
      new URLSearchParams({
        grant_type: "client_credentials",
        client_id: config.clientId,
        client_secret: config.clientSecret,
      }),
      {
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
      }
    );

    return response.data.access_token;
  } catch (error) {
    console.error("Token Error:", error.response?.data || error.message);
    throw new Error("Failed to get access token");
  }
}

// Create payment
app.post("/api/payments/create", async (req, res) => {
  try {
    const {
      amount,
      currency = "USD",
      orderId,
      title,
      description,
      notifyUrl,
      redirectUrl,
      environment = "sandbox",
    } = req.body;

    // Validate required fields
    if (!amount || !orderId || !title) {
      return res.status(400).json({
        success: false,
        error: "Missing required fields: amount, orderId, title",
      });
    }

    const config = UNIPAYMENT_CONFIG[environment];
    const accessToken = await getAccessToken(environment);

    const payload = {
      price_amount: parseFloat(amount),
      price_currency: currency,
      order_id: orderId,
      title: title,
      description: description || title,
      notify_url: notifyUrl,
      redirect_url: redirectUrl,
      lang: "en",
    };

    console.log("Creating payment with payload:", payload);

    const response = await axios.post(
      `${config.baseUrl}/v1.0/invoices`,
      payload,
      {
        headers: {
          Authorization: `Bearer ${accessToken}`,
          "Content-Type": "application/json",
        },
      }
    );

    console.log("Payment created successfully:", response.data);

    res.json({
      success: true,
      data: response.data.data,
    });
  } catch (error) {
    console.error(
      "Payment creation error:",
      error.response?.data || error.message
    );

    res.status(500).json({
      success: false,
      error: error.response?.data?.msg || error.message,
      details: error.response?.data,
    });
  }
});

// Get payment status
app.get("/api/payments/:invoiceId", async (req, res) => {
  try {
    const { invoiceId } = req.params;
    const { environment = "sandbox" } = req.query;

    const config = UNIPAYMENT_CONFIG[environment];
    const accessToken = await getAccessToken(environment);

    const response = await axios.get(
      `${config.baseUrl}/v1.0/invoices/${invoiceId}`,
      {
        headers: {
          Authorization: `Bearer ${accessToken}`,
        },
      }
    );

    res.json({
      success: true,
      data: response.data.data,
    });
  } catch (error) {
    console.error(
      "Payment status error:",
      error.response?.data || error.message
    );

    res.status(500).json({
      success: false,
      error: error.response?.data?.msg || error.message,
    });
  }
});

// Health check
app.get("/health", (req, res) => {
  res.json({ status: "OK", service: "UniPayment Service" });
});

// Start server
app.listen(PORT, () => {
  console.log(`UniPayment service running on port ${PORT}`);
  console.log(`Health check: http://localhost:${PORT}/health`);
});
