<script>
  import { Button } from "$lib/ui/button";
  import { Input } from "$lib/ui/input";
  import { Label } from "$lib/ui/label";
  import { Card, CardContent, CardHeader, CardTitle } from "$lib/ui/card";
  import { Alert, AlertDescription } from "$lib/ui/alert";
  import { Separator } from "$lib/ui/separator";
  import {
    Mail,
    Download,
    Calculator,
    RotateCcw,
    AlertCircle,
    CheckCircle,
  } from "@lucide/svelte";

  let purchasePrice = "";
  let bondAmount = "";
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

  async function calculateCosts() {
    if (!purchasePrice || parseFloat(purchasePrice) <= 0) {
      error = "Please enter a valid transfer amount";
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
            bond_amount: bondAmount ? parseFloat(bondAmount) : 0,
          }),
        },
      );
      console.log(response);

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
            bond_amount: results.bond_amount || 0,
            total: results.total,
            grand_total: results.grand_total || results.total,
          },
          breakdown: results.breakdown,
          bond_breakdown: results.bond_breakdown || null,
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
            bond_amount: results.bond_amount || 0,
            total: results.total,
            grand_total: results.grand_total || results.total,
          },
          breakdown: results.breakdown,
          bond_breakdown: results.bond_breakdown || null,
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
    bondAmount = "";
    results = null;
    error = "";
    emailAddress = "";
    emailSuccess = "";
  }
</script>

<div class="w-full max-w-2xl mx-auto space-y-6">
  <Card>
    <CardHeader>
      <CardTitle class="flex items-center gap-2">
        <Calculator class="h-5 w-5" />
        Transfer Bond Costs
      </CardTitle>
    </CardHeader>
    <CardContent class="space-y-4">
      <div class="space-y-4">
        <div class="space-y-2">
          <Label for="transfer-amount">Transfer Amount</Label>
          <Input
            id="transfer-amount"
            type="number"
            placeholder="500000"
            bind:value={purchasePrice}
            disabled={loading}
            step="1000"
            min="0"
          />
        </div>

        <div class="space-y-2">
          <Label for="bond-amount">Bond Amount</Label>
          <Input
            id="bond-amount"
            type="number"
            placeholder="4000000"
            bind:value={bondAmount}
            disabled={loading}
            step="1000"
            min="0"
          />
        </div>

        <div class="flex gap-2">
          <Button
            onclick={calculateCosts}
            disabled={loading || !purchasePrice}
            class="flex-1"
          >
            <Calculator class="h-4 w-4 mr-2" />
            {loading ? "Calculating..." : "Calculate"}
          </Button>
          <Button variant="outline" onclick={reset}>
            <RotateCcw class="h-4 w-4 mr-2" />
            Reset
          </Button>
        </div>
      </div>
    </CardContent>
  </Card>

  {#if error}
    <Alert variant="destructive">
      <AlertCircle class="h-4 w-4" />
      <AlertDescription>{error}</AlertDescription>
    </Alert>
  {/if}

  {#if emailSuccess}
    <Alert>
      <CheckCircle class="h-4 w-4" />
      <AlertDescription>{emailSuccess}</AlertDescription>
    </Alert>
  {/if}

  {#if results}
    <Card>
      <CardHeader>
        <CardTitle>Cost Breakdown</CardTitle>
      </CardHeader>
      <CardContent class="space-y-6">
        <div class="text-center space-y-1">
          <p class="text-sm text-muted-foreground">Transfer cost on:</p>
          <p class="text-xl font-bold">
            {formatCurrency(results.purchase_price)}
          </p>
          {#if results.bond_amount && results.bond_amount > 0}
            <p class="text-sm text-muted-foreground">Bond cost on:</p>
            <p class="text-xl font-bold">
              {formatCurrency(results.bond_amount)}
            </p>
          {/if}
        </div>

        <div class="space-y-4">
          <!-- Transfer Costs -->
          <div class="space-y-2">
            <h4 class="font-semibold text-sm text-muted-foreground">
              Transfer Cost on: {formatCurrency(results.purchase_price)}
            </h4>

            <!-- Government Costs -->
            {#if results.breakdown.government_costs}
              <div class="space-y-1 pl-2">
                <p class="text-xs font-medium text-muted-foreground">
                  Government Costs:
                </p>
                {#each Object.entries(results.breakdown.government_costs) as [key, item]}
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm">{item.label}</span>
                    <span class="font-mono text-sm"
                      >{formatCurrency(item.amount)}</span
                    >
                  </div>
                {/each}
              </div>
            {/if}

            <!-- Attorney Costs -->
            {#if results.breakdown.attorney_costs}
              <div class="space-y-1 pl-2">
                <p class="text-xs font-medium text-muted-foreground">
                  Attorney Costs:
                </p>
                {#each Object.entries(results.breakdown.attorney_costs) as [key, item]}
                  <div class="flex justify-between items-center py-1">
                    <span class="text-sm">{item.label}</span>
                    <span class="font-mono text-sm"
                      >{formatCurrency(item.amount)}</span
                    >
                  </div>
                {/each}
              </div>
            {/if}

            <!-- VAT -->
            {#if results.breakdown.vat}
              <div class="flex justify-between items-center py-1 pl-2">
                <span class="text-sm">{results.breakdown.vat.label}</span>
                <span class="font-mono text-sm"
                  >{formatCurrency(results.breakdown.vat.amount)}</span
                >
              </div>
            {/if}

            <div
              class="flex justify-between items-center py-1 font-semibold text-sm border-t pt-2"
            >
              <span>Sub Total</span>
              <span class="font-mono">{formatCurrency(results.total)}</span>
            </div>
          </div>

          <!-- Bond Costs (if applicable) -->
          {#if results.bond_amount && results.bond_amount > 0 && results.bond_breakdown}
            <Separator />

            <div class="space-y-2">
              <h4 class="font-semibold text-sm text-muted-foreground">
                Bond Cost on: {formatCurrency(results.bond_amount)}
              </h4>

              {#if results.bond_breakdown.government_costs}
                <div class="space-y-1 pl-2">
                  <p class="text-xs font-medium text-muted-foreground">
                    Government Costs:
                  </p>
                  {#each Object.entries(results.bond_breakdown.government_costs) as [key, item]}
                    <div class="flex justify-between items-center py-1">
                      <span class="text-sm">{item.label}</span>
                      <span class="font-mono text-sm"
                        >{formatCurrency(item.amount)}</span
                      >
                    </div>
                  {/each}
                </div>
              {/if}

              {#if results.bond_breakdown.attorney_costs}
                <div class="space-y-1 pl-2">
                  <p class="text-xs font-medium text-muted-foreground">
                    Attorney Costs:
                  </p>
                  {#each Object.entries(results.bond_breakdown.attorney_costs) as [key, item]}
                    <div class="flex justify-between items-center py-1">
                      <span class="text-sm">{item.label}</span>
                      <span class="font-mono text-sm"
                        >{formatCurrency(item.amount)}</span
                      >
                    </div>
                  {/each}
                </div>
              {/if}

              {#if results.bond_breakdown.vat}
                <div class="flex justify-between items-center py-1 pl-2">
                  <span class="text-sm">{results.bond_breakdown.vat.label}</span
                  >
                  <span class="font-mono text-sm"
                    >{formatCurrency(results.bond_breakdown.vat.amount)}</span
                  >
                </div>
              {/if}

              <div
                class="flex justify-between items-center py-1 font-semibold text-sm border-t pt-2"
              >
                <span>Sub Total</span>
                <span class="font-mono"
                  >{formatCurrency(results.bond_total || 0)}</span
                >
              </div>
            </div>
          {/if}

          <!-- Grand Total -->
          <Separator />
          <div class="flex justify-between items-center py-2 font-bold text-lg">
            <span>Total</span>
            <span class="font-mono"
              >{formatCurrency(results.grand_total || results.total)}</span
            >
          </div>
        </div>

        <Separator />

        <!-- Email Section -->
        <div class="space-y-3">
          <Label for="email">Email Results</Label>
          <div class="flex gap-2">
            <Input
              id="email"
              type="email"
              placeholder="Enter your email address"
              bind:value={emailAddress}
              disabled={sendingEmail}
              class="flex-1"
            />
            <Button
              on:click={sendEmail}
              disabled={!emailAddress || sendingEmail}
              variant="outline"
            >
              <Mail class="h-4 w-4 mr-2" />
              {sendingEmail ? "Sending..." : "Send"}
            </Button>
          </div>
        </div>

        <!-- Download Button -->
        <Button
          on:click={downloadPdf}
          disabled={generatingPdf}
          class="w-full"
          size="lg"
        >
          <Download class="h-4 w-4 mr-2" />
          {generatingPdf ? "Generating PDF..." : "Download Results"}
        </Button>
      </CardContent>
    </Card>

    <!-- Disclaimer Card -->
    <Card>
      <CardHeader>
        <CardTitle class="text-sm">Important Information</CardTitle>
      </CardHeader>
      <CardContent class="text-xs space-y-3">
        <div>
          <p class="font-semibold mb-2">
            PROVISION MUST BE MADE FOR THE FOLLOWING AMOUNTS:
          </p>
          <ul class="space-y-1 text-muted-foreground list-disc pl-4">
            <li>Bank admin and initiation fees of approximately R6,037.50</li>
            <li>Levies for up to 12 months (normally 3 months)</li>
            <li>
              Transfer of an Exclusive Use Area amount of approximately
              R2,000.00 per Exclusive Use Area
            </li>
            <li>
              Insurance Certificate for Sectional Title transfers in the sum of
              approx. R750.00
            </li>
            <li>
              Please note with Sectional Title that there are additional charges
              for extra Units and Exclusive Use Areas
            </li>
            <li>
              Additional lodgement fee payable to the deeds office in respect of
              each transfer, mortgage bond, cancellation or cession in the sum
              of R69.00
            </li>
          </ul>
        </div>

        <Separator />

        <div class="space-y-2">
          <p>Please note fees here are calculated up to R500,000,000.00</p>
          <p>
            For quotes in excess of R500,000,000.00, and for more accurate
            calculations, please contact us.
          </p>
        </div>

        <Separator />

        <div>
          <p class="font-semibold">Disclaimer:</p>
          <p class="text-muted-foreground">
            All estimated calculations done here are provided for general
            information purposes only and do not constitute professional advice.
            We do not warrant the correctness of this information. For more
            accurate calculations, please contact us.
          </p>
        </div>
      </CardContent>
    </Card>
  {/if}
</div>
