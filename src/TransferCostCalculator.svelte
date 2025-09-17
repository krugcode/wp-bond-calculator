<script>
  import { onMount } from "svelte";

  let purchasePrice = "";
  let loading = false;
  let results = null;
  let error = "";
  let emailAddress = "";
  let sendingEmail = false;
  let generatingPdf = false;
  let emailSuccess = "";

  function formatCurrency(amount) {
    return new Intl.NumberFormat("en-ZA", {
      style: "currency",
      currency: "ZAR",
      minimumFractionDigits: 2,
    }).format(amount);
  }

  function formatNumber(value) {
    return value.replace(/[^\d]/g, "");
  }

  function handlePriceInput(event) {
    const value = formatNumber(event.target.value);
    purchasePrice = value;
    event.target.value = value ? formatCurrency(parseFloat(value)) : "";
  }

  async function calculateCosts() {
    if (!purchasePrice || parseFloat(purchasePrice) <= 0) {
      error = "Please enter a valid purchase price";
      return;
    }

    loading = true;
    error = "";
    results = null;

    try {
      const response = await fetch(
        `${window.bcAjax.apiUrl}calculate-transfer-cost`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": window.bcAjax.nonce,
          },
          body: JSON.stringify({
            purchase_price: parseFloat(purchasePrice),
          }),
        },
      );

      const data = await response.json();

      if (data.success) {
        results = data;
      } else {
        error = data.message || "Calculation failed";
      }
    } catch (err) {
      error = "Network error. Please try again.";
    } finally {
      loading = false;
    }
  }

  async function downloadPdf() {
    if (!results) return;

    generatingPdf = true;
    error = "";

    try {
      const response = await fetch(`${window.bcAjax.apiUrl}generate-pdf`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.bcAjax.nonce,
        },
        body: JSON.stringify({
          type: "transfer",
          data: {
            purchase_price: results.purchase_price,
            total: results.total,
          },
          breakdown: results.breakdown,
        }),
      });

      const data = await response.json();

      if (data.success) {
        window.open(data.pdf_url, "_blank");
      } else {
        error = data.message || "PDF generation failed";
      }
    } catch (err) {
      error = "Network error. Please try again.";
    } finally {
      generatingPdf = false;
    }
  }

  async function sendEmail() {
    if (!emailAddress || !results) return;

    sendingEmail = true;
    error = "";
    emailSuccess = "";

    try {
      // First generate PDF
      const pdfResponse = await fetch(`${window.bcAjax.apiUrl}generate-pdf`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.bcAjax.nonce,
        },
        body: JSON.stringify({
          type: "transfer",
          data: {
            purchase_price: results.purchase_price,
            total: results.total,
          },
          breakdown: results.breakdown,
        }),
      });

      const pdfData = await pdfResponse.json();

      if (!pdfData.success) {
        error = pdfData.message || "PDF generation failed";
        return;
      }

      // Then send email
      const emailResponse = await fetch(`${window.bcAjax.apiUrl}send-email`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": window.bcAjax.nonce,
        },
        body: JSON.stringify({
          email: emailAddress,
          type: "transfer",
          pdf_url: pdfData.pdf_url,
        }),
      });

      const emailData = await emailResponse.json();

      if (emailData.success) {
        emailSuccess = "Email sent successfully!";
        emailAddress = "";
      } else {
        error = emailData.message || "Email sending failed";
      }
    } catch (err) {
      error = "Network error. Please try again.";
    } finally {
      sendingEmail = false;
    }
  }

  function reset() {
    purchasePrice = "";
    results = null;
    error = "";
    emailAddress = "";
    emailSuccess = "";
  }
</script>

<div class="transfer-calculator">
  <h3>Transfer Bond Costs</h3>

  <div class="form-section">
    <div class="input-group">
      <label for="transfer-amount">Transfer Amount</label>
      <input
        id="transfer-amount"
        type="text"
        placeholder="R500,000.00"
        on:input={handlePriceInput}
        disabled={loading}
      />
    </div>

    <div class="button-group">
      <button
        on:click={calculateCosts}
        disabled={loading || !purchasePrice}
        class="calculate-btn"
      >
        {loading ? "Calculating..." : "Calculate"}
      </button>
      <button on:click={reset} class="reset-btn">Reset</button>
    </div>
  </div>

  {#if error}
    <div class="error-message">{error}</div>
  {/if}

  {#if emailSuccess}
    <div class="success-message">{emailSuccess}</div>
  {/if}

  {#if results}
    <div class="results-section">
      <div class="cost-breakdown">
        <h4>Transfer cost on: {formatCurrency(results.purchase_price)}</h4>

        <!-- Government Costs -->
        <div class="cost-section">
          <h5>Government Costs:</h5>
          {#each Object.entries(results.breakdown.government_costs) as [key, item]}
            <div class="cost-item">
              <span class="label">{item.label}</span>
              <span class="amount">{formatCurrency(item.amount)}</span>
            </div>
          {/each}
        </div>

        <!-- Attorney Costs -->
        <div class="cost-section">
          <h5>Attorneys Costs:</h5>
          {#each Object.entries(results.breakdown.attorney_costs) as [key, item]}
            <div class="cost-item">
              <span class="label">{item.label}</span>
              <span class="amount">{formatCurrency(item.amount)}</span>
            </div>
          {/each}
        </div>

        <!-- VAT -->
        <div class="cost-item">
          <span class="label">{results.breakdown.vat.label}</span>
          <span class="amount"
            >{formatCurrency(results.breakdown.vat.amount)}</span
          >
        </div>

        <div class="cost-item total">
          <span class="label">Sub Total</span>
          <span class="amount">{formatCurrency(results.total)}</span>
        </div>
      </div>

      <!-- Email Section -->
      <div class="email-section">
        <h4>Email Address</h4>
        <input
          type="email"
          bind:value={emailAddress}
          placeholder="Enter your email"
          disabled={sendingEmail}
        />
        <button
          on:click={sendEmail}
          disabled={!emailAddress || sendingEmail}
          class="email-btn"
        >
          {sendingEmail ? "Sending..." : "Send the results via email"}
        </button>
      </div>

      <!-- Download Button -->
      <button
        on:click={downloadPdf}
        disabled={generatingPdf}
        class="download-btn"
      >
        {generatingPdf ? "Generating..." : "DOWNLOAD RESULTS"}
      </button>
    </div>
  {/if}

  <!-- Disclaimer -->
  <div class="disclaimer">
    <p><strong>PROVISION MUST BE MADE FOR THE FOLLOWING AMOUNTS:</strong></p>
    <ul>
      <li>Bank admin and initiation fees of approximately R6,037.50</li>
      <li>Levies for up to 12 months (normally 3 months)</li>
      <li>
        Transfer of an Exclusive Use Area amount of approximately R2,000.00 per
        Exclusive Use Area
      </li>
      <li>
        Insurance Certificate for Sectional Title transfers in the sum of
        approx. R750.00
      </li>
      <li>
        Please note with Sectional Title that there are additional charges for
        extra Units and Exclusive Use Areas
      </li>
      <li>
        Additional lodgement fee payable to the deeds office in respect of each
        transfer, mortgage bond, cancellation or cession in the sum of R69.00
      </li>
    </ul>

    <p>Please note fees here are calculated up to R500,000,000.00</p>
    <p>
      For quotes in excess of R500,000,000.00, and for more accurate
      calculations, please contact us.
    </p>

    <p>
      <strong>Disclaimer:</strong> All estimated calculations done here are provided
      for general information purposes only and do not constitute professional advice.
      We do not warrant the correctness of this information. For more accurate calculations,
      please contact us.
    </p>
  </div>
</div>

<style>
  .transfer-calculator {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
    font-family: Arial, sans-serif;
    background: #f8f9fa;
    border-radius: 8px;
  }

  .form-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  }

  .input-group {
    margin-bottom: 15px;
  }

  .input-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #333;
  }

  .input-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 16px;
    box-sizing: border-box;
  }

  .button-group {
    display: flex;
    gap: 10px;
  }

  .calculate-btn {
    flex: 1;
    padding: 12px;
    background: #007cba;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.2s;
  }

  .calculate-btn:hover:not(:disabled) {
    background: #005a87;
  }

  .calculate-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  .reset-btn {
    padding: 12px 20px;
    background: #6c757d;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }

  .reset-btn:hover {
    background: #5a6268;
  }

  .results-section {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
  }

  .cost-breakdown h4 {
    margin: 0 0 15px 0;
    font-size: 18px;
    color: #333;
  }

  .cost-section {
    margin-bottom: 15px;
  }

  .cost-section h5 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
  }

  .cost-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
  }

  .cost-item.total {
    border-top: 2px solid #333;
    border-bottom: none;
    font-weight: bold;
    margin-top: 10px;
    padding-top: 15px;
  }

  .label {
    color: #666;
  }

  .amount {
    font-weight: bold;
    color: #333;
  }

  .email-section {
    margin: 20px 0;
    padding: 15px 0;
    border-top: 1px solid #eee;
  }

  .email-section h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
  }

  .email-section input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    box-sizing: border-box;
  }

  .email-btn {
    width: 100%;
    padding: 10px;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
  }

  .email-btn:hover:not(:disabled) {
    background: #218838;
  }

  .email-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  .download-btn {
    width: 100%;
    padding: 15px;
    background: #ff8c00;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    margin-top: 10px;
  }

  .download-btn:hover:not(:disabled) {
    background: #e67e00;
  }

  .download-btn:disabled {
    background: #ccc;
    cursor: not-allowed;
  }

  .error-message {
    background: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
  }

  .success-message {
    background: #d4edda;
    color: #155724;
    padding: 10px;
    border-radius: 4px;
    margin: 10px 0;
  }

  .disclaimer {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    font-size: 12px;
    color: #666;
    line-height: 1.4;
  }

  .disclaimer p {
    margin: 10px 0;
  }

  .disclaimer ul {
    margin: 10px 0;
    padding-left: 20px;
  }

  .disclaimer li {
    margin: 5px 0;
  }
</style>
