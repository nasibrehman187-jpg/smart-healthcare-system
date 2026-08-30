// =====================================================
// Smart Healthcare & Diagnostic Management System
// PART 2: C++ Console Program — Patient Fee & Fine Calculator
// DIT 2nd Semester Final Project
// =====================================================
// This is a standalone menu-driven C++ console application.
//
// FEATURES:
//   1. Function calculateFine(int daysLate):
//      - Charges Rs. 50 per day late
//      - Capped at maximum Rs. 2000
//   2. Function calculateDiscount(double amount, string category):
//      - "sibling" = 10% discount
//      - "senior"  = 15% discount
//      - "none"    = 0% discount
//   3. Formatted receipt output using <iomanip> (fixed, setprecision(2))
//   4. Input validation and do-while menu loop
// =====================================================

#include <iostream>
#include <iomanip>
#include <string>
#include <algorithm>
#include <cctype>

using namespace std;

// =====================================================
// FUNCTION 1: calculateFine
// Calculates late payment fine based on days overdue
// Parameters:
//   daysLate - Number of days payment is past due date
// Returns:
//   Fine amount in PKR (Rs. 50/day, capped at Rs. 2000)
// =====================================================
double calculateFine(int daysLate) {
    if (daysLate <= 0) {
        return 0.0; // No fine if paid on time or early
    }
    
    // Rs. 50 per day late
    double fine = daysLate * 50.0;
    
    // Cap fine at maximum Rs. 2000
    if (fine > 2000.0) {
        fine = 2000.0;
    }
    
    return fine;
}

// =====================================================
// FUNCTION 2: calculateDiscount
// Calculates discount amount based on patient category
// Parameters:
//   amount   - Subtotal amount before discount
//   category - Patient category ("sibling", "senior", "none")
// Returns:
//   Discount amount in PKR
// =====================================================
double calculateDiscount(double amount, string category) {
    // Convert input string to lowercase for case-insensitive matching
    for (size_t i = 0; i < category.length(); i++) {
        category[i] = tolower(category[i]);
    }
    
    double discountRate = 0.0;
    
    if (category == "sibling") {
        discountRate = 0.10; // 10% discount for sibling category
    } else if (category == "senior") {
        discountRate = 0.15; // 15% discount for senior citizen category
    } else {
        discountRate = 0.00; // 0% discount for regular/none
    }
    
    return amount * discountRate;
}

// Helper function to clear invalid stream input
void clearInputBuffer() {
    cin.clear();
    cin.ignore(10000, '\n');
}

// =====================================================
// MAIN FUNCTION — Menu-driven flow with do-while loop
// =====================================================
int main() {
    int choice = 0;
    
    do {
        // Display Main Menu
        cout << "\n=====================================================\n";
        cout << "   SMART HEALTHCARE - PATIENT FEE & FINE CALCULATOR  \n";
        cout << "=====================================================\n";
        cout << "1. Calculate Patient Bill & Fine\n";
        cout << "2. Exit Program\n";
        cout << "-----------------------------------------------------\n";
        cout << "Enter your choice (1-2): ";
        
        if (!(cin >> choice)) {
            cout << "\n[!] Invalid input! Please enter a number (1 or 2).\n";
            clearInputBuffer();
            continue;
        }
        
        if (choice == 1) {
            double baseFee = 0.0;
            int daysLate = 0;
            string category = "";
            
            // --- Input Base Fee ---
            cout << "\nEnter Base Consultation / Treatment Fee (PKR): ";
            while (!(cin >> baseFee) || baseFee < 0) {
                cout << "[!] Invalid fee! Please enter a non-negative number: ";
                clearInputBuffer();
            }
            
            // --- Input Days Overdue ---
            cout << "Enter Days Overdue / Late (0 if paid on time): ";
            while (!(cin >> daysLate) || daysLate < 0) {
                cout << "[!] Invalid days! Please enter 0 or a positive integer: ";
                clearInputBuffer();
            }
            
            // --- Input Discount Category ---
            cout << "Enter Patient Category (sibling / senior / none): ";
            cin >> category;
            
            // Ensure valid category choice
            string catLower = category;
            for (size_t i = 0; i < catLower.length(); i++) catLower[i] = tolower(catLower[i]);
            
            while (catLower != "sibling" && catLower != "senior" && catLower != "none") {
                cout << "[!] Invalid category! Choose (sibling / senior / none): ";
                cin >> category;
                catLower = category;
                for (size_t i = 0; i < catLower.length(); i++) catLower[i] = tolower(catLower[i]);
            }
            
            // --- COMPUTATIONS ---
            double fineAmount = calculateFine(daysLate);
            double subtotal = baseFee + fineAmount;
            double discountAmount = calculateDiscount(subtotal, category);
            double finalTotal = subtotal - discountAmount;
            
            // --- PRINT FORMATTED RECEIPT ---
            cout << "\n=====================================================\n";
            cout << "                OFFICIAL BILL RECEIPT                \n";
            cout << "=====================================================\n";
            cout << fixed << setprecision(2);
            cout << left << setw(30) << "Base Consultation Fee:"  << "Rs. " << right << setw(10) << baseFee << "\n";
            cout << left << setw(30) << "Days Overdue:"           << "    " << right << setw(10) << daysLate << " days\n";
            cout << left << setw(30) << "Late Fine (Rs. 50/day):" << "Rs. " << right << setw(10) << fineAmount << "\n";
            if (fineAmount >= 2000.0) {
                cout << "   (* Late fine capped at maximum Rs. 2000.00)\n";
            }
            cout << "-----------------------------------------------------\n";
            cout << left << setw(30) << "Subtotal Amount:"        << "Rs. " << right << setw(10) << subtotal << "\n";
            cout << left << setw(30) << ("Discount (" + category + "):") << "-Rs. " << right << setw(9) << discountAmount << "\n";
            cout << "=====================================================\n";
            cout << left << setw(30) << "FINAL TOTAL PAYABLE:"    << "Rs. " << right << setw(10) << finalTotal << "\n";
            cout << "=====================================================\n";
            
        } else if (choice == 2) {
            cout << "\nThank you for using Smart Healthcare System. Goodbye!\n\n";
        } else {
            cout << "\n[!] Invalid choice! Please select 1 or 2.\n";
        }
        
    } while (choice != 2);
    
    return 0;
}
