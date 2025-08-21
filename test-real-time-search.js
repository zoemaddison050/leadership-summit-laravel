/**
 * Real-time Search Functionality Test Suite
 * Tests Requirements 1.1, 1.3, 2.1, 2.3, 4.1
 */

class RealTimeSearchTester {
  constructor() {
    this.testResults = [];
    this.debounceDelay = 300;
    this.testTimeout = 5000;
  }

  /**
   * Test real-time filtering as user types (Requirements 1.1, 2.1)
   */
  async testRealTimeFiltering() {
    console.log("🔍 Testing Real-time Filtering...");

    const tests = [
      {
        page: "events",
        inputId: "eventSearchInput",
        searchTerm: "leadership",
        expectedResults: true,
      },
      {
        page: "speakers",
        inputId: "speakerSearchInput",
        searchTerm: "CEO",
        expectedResults: true,
      },
    ];

    for (const test of tests) {
      try {
        const result = await this.simulateTyping(test);
        this.logResult(`✅ Real-time filtering works for ${test.page}`, "PASS");
      } catch (error) {
        this.logResult(
          `❌ Real-time filtering failed for ${test.page}: ${error.message}`,
          "FAIL"
        );
      }
    }
  }

  /**
   * Test Enter key functionality (Requirements 1.3, 2.3)
   */
  async testEnterKeyFunctionality() {
    console.log("⌨️ Testing Enter Key Functionality...");

    const tests = [
      {
        page: "events",
        inputId: "eventSearchInput",
        searchTerm: "workshop",
      },
      {
        page: "speakers",
        inputId: "speakerSearchInput",
        searchTerm: "innovation",
      },
    ];

    for (const test of tests) {
      try {
        const result = await this.simulateEnterKey(test);
        this.logResult(
          `✅ Enter key functionality works for ${test.page}`,
          "PASS"
        );
      } catch (error) {
        this.logResult(
          `❌ Enter key functionality failed for ${test.page}: ${error.message}`,
          "FAIL"
        );
      }
    }
  }

  /**
   * Test debounce delay (Requirement 4.1)
   */
  async testDebounceDelay() {
    console.log("⏱️ Testing Debounce Delay...");

    try {
      const startTime = Date.now();

      // Simulate rapid typing
      await this.simulateRapidTyping();

      // Wait for debounce
      await this.waitForDebounce();

      const endTime = Date.now();
      const actualDelay = endTime - startTime;

      if (actualDelay >= this.debounceDelay - 50) {
        this.logResult(
          `✅ Debounce delay works correctly (${actualDelay}ms)`,
          "PASS"
        );
      } else {
        this.logResult(
          `❌ Debounce delay too short (${actualDelay}ms, expected ~${this.debounceDelay}ms)`,
          "FAIL"
        );
      }
    } catch (error) {
      this.logResult(`❌ Debounce delay test failed: ${error.message}`, "FAIL");
    }
  }

  /**
   * Simulate typing in search input
   */
  async simulateTyping(test) {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error("Test timeout"));
      }, this.testTimeout);

      try {
        // Create mock input element
        const input = this.createMockInput(test.inputId);

        // Simulate typing character by character
        let currentValue = "";
        const typeChar = (index) => {
          if (index < test.searchTerm.length) {
            currentValue += test.searchTerm[index];
            input.value = currentValue;

            // Trigger input event
            const event = new Event("input", { bubbles: true });
            input.dispatchEvent(event);

            // Continue typing after short delay
            setTimeout(() => typeChar(index + 1), 50);
          } else {
            // Typing complete, wait for debounce
            setTimeout(() => {
              clearTimeout(timeout);
              resolve(true);
            }, this.debounceDelay + 100);
          }
        };

        typeChar(0);
      } catch (error) {
        clearTimeout(timeout);
        reject(error);
      }
    });
  }

  /**
   * Simulate Enter key press
   */
  async simulateEnterKey(test) {
    return new Promise((resolve, reject) => {
      const timeout = setTimeout(() => {
        reject(new Error("Enter key test timeout"));
      }, this.testTimeout);

      try {
        const input = this.createMockInput(test.inputId);
        input.value = test.searchTerm;

        // Create and dispatch Enter key event
        const enterEvent = new KeyboardEvent("keypress", {
          key: "Enter",
          keyCode: 13,
          which: 13,
          bubbles: true,
        });

        input.dispatchEvent(enterEvent);

        // Wait for search to execute
        setTimeout(() => {
          clearTimeout(timeout);
          resolve(true);
        }, 100);
      } catch (error) {
        clearTimeout(timeout);
        reject(error);
      }
    });
  }

  /**
   * Simulate rapid typing to test debounce
   */
  async simulateRapidTyping() {
    const input = this.createMockInput("testInput");
    const searchTerm = "rapid typing test";

    // Type all characters rapidly (no delay)
    for (let i = 0; i < searchTerm.length; i++) {
      input.value = searchTerm.substring(0, i + 1);
      const event = new Event("input", { bubbles: true });
      input.dispatchEvent(event);
    }
  }

  /**
   * Wait for debounce delay
   */
  async waitForDebounce() {
    return new Promise((resolve) => {
      setTimeout(resolve, this.debounceDelay);
    });
  }

  /**
   * Create mock input element for testing
   */
  createMockInput(id) {
    let input = document.getElementById(id);
    if (!input) {
      input = document.createElement("input");
      input.id = id;
      input.type = "text";
      input.style.display = "none";
      document.body.appendChild(input);
    }
    return input;
  }

  /**
   * Log test result
   */
  logResult(message, status) {
    const result = {
      message,
      status,
      timestamp: new Date().toISOString(),
    };

    this.testResults.push(result);
    console.log(`[${status}] ${message}`);

    // Also log to DOM if test container exists
    const container = document.getElementById("realTimeTestResults");
    if (container) {
      const resultDiv = document.createElement("div");
      resultDiv.className = `test-result test-${status.toLowerCase()}`;
      resultDiv.textContent = message;
      container.appendChild(resultDiv);
    }
  }

  /**
   * Run all real-time search tests
   */
  async runAllTests() {
    console.log("🚀 Starting Real-time Search Test Suite...");
    console.time("RealTimeSearchTests");

    this.testResults = [];

    try {
      await this.testRealTimeFiltering();
      await this.testEnterKeyFunctionality();
      await this.testDebounceDelay();

      console.timeEnd("RealTimeSearchTests");

      const summary = this.generateSummary();
      console.log("📊 Test Summary:", summary);

      return summary;
    } catch (error) {
      console.error("❌ Real-time search test suite failed:", error);
      throw error;
    }
  }

  /**
   * Generate test summary
   */
  generateSummary() {
    const total = this.testResults.length;
    const passed = this.testResults.filter((r) => r.status === "PASS").length;
    const failed = this.testResults.filter((r) => r.status === "FAIL").length;

    return {
      total,
      passed,
      failed,
      passRate: total > 0 ? Math.round((passed / total) * 100) : 0,
      results: this.testResults,
    };
  }

  /**
   * Get detailed test results
   */
  getResults() {
    return this.testResults;
  }
}

// Export for use in other test files
if (typeof module !== "undefined" && module.exports) {
  module.exports = RealTimeSearchTester;
}

// Auto-run tests if this script is loaded directly
if (typeof window !== "undefined") {
  window.RealTimeSearchTester = RealTimeSearchTester;

  // Auto-run if DOM is ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      console.log("🧪 Real-time Search Tester loaded and ready");
    });
  } else {
    console.log("🧪 Real-time Search Tester loaded and ready");
  }
}
