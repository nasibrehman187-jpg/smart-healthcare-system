' =====================================================
' Smart Healthcare & Diagnostic Management System
' PART 3: VB.NET Windows Forms App — Mini Hospital Billing
' DIT 2nd Semester Final Project
' =====================================================
' This desktop application calculates patient hospital bills.
'
' FORMULAS & RULES:
'   1. Room Total = Days Admitted * Daily Room Charge
'   2. Subtotal = Consultation Fee + Room Total
'   3. Insurance Discount = 20% of Subtotal (if Insured), else 0
'   4. Final Total Payable = Subtotal - Insurance Discount
'
' FEATURES:
'   - Input validation for empty & non-numeric values
'   - Formatted PKR currency output (Rs. X,XXX.XX)
'   - Reset form button to clear all inputs
Imports System.Windows.Forms

Public Class Form1

    ''' <summary>
    ''' Event Handler: Calculate Total Bill
    ''' Triggers when user clicks the "Calculate Total Hospital Bill" button
    ''' </summary>
    Private Sub btnCalculate_Click(sender As Object, e As EventArgs) Handles btnCalculate.Click
        ' --- Step 1: Input Validation ---

        ' Validate Patient Name
        If String.IsNullOrWhiteSpace(txtPatientName.Text) Then
            MessageBox.Show("Please enter the patient's full name.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning)
            txtPatientName.Focus()
            Exit Sub
        End If

        ' Validate Consultation Fee
        Dim consultationFee As Decimal = 0
        If Not Decimal.TryParse(txtConsultationFee.Text, consultationFee) OrElse consultationFee < 0 Then
            MessageBox.Show("Please enter a valid, non-negative Consultation Fee.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning)
            txtConsultationFee.Focus()
            Exit Sub
        End If

        ' Validate Days Admitted
        Dim daysAdmitted As Integer = 0
        If Not Integer.TryParse(txtDaysAdmitted.Text, daysAdmitted) OrElse daysAdmitted < 0 Then
            MessageBox.Show("Please enter a valid non-negative integer for Days Admitted.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning)
            txtDaysAdmitted.Focus()
            Exit Sub
        End If

        ' Validate Daily Room Charge
        Dim dailyRoomCharge As Decimal = 0
        If Not Decimal.TryParse(txtDailyRoomCharge.Text, dailyRoomCharge) OrElse dailyRoomCharge < 0 Then
            MessageBox.Show("Please enter a valid, non-negative Daily Room Charge.", "Validation Error", MessageBoxButtons.OK, MessageBoxIcon.Warning)
            txtDailyRoomCharge.Focus()
            Exit Sub
        End If

        ' --- Step 2: Calculations ---

        ' Calculate total room accommodation charge
        Dim roomTotal As Decimal = daysAdmitted * dailyRoomCharge

        ' Subtotal = Consultation Fee + Room Total
        Dim subtotal As Decimal = consultationFee + roomTotal

        ' Insurance Discount = 20% of Subtotal if Insured, else 0
        Dim discountPercent As Decimal = If(rdoInsured.Checked, 0.20D, 0.0D)
        Dim discountAmount As Decimal = subtotal * discountPercent

        ' Final Total Payable = Subtotal - Discount
        Dim totalPayable As Decimal = subtotal - discountAmount

        ' --- Step 3: Display Results ---
        lblSubtotalValue.Text = String.Format("Rs. {0:N2}", subtotal)

        If rdoInsured.Checked Then
            lblDiscountValue.Text = String.Format("- Rs. {0:N2} (20%)", discountAmount)
            lblDiscountValue.ForeColor = System.Drawing.Color.FromArgb(5, 150, 105) ' Green
        Else
            lblDiscountValue.Text = "Rs. 0.00 (No Discount)"
            lblDiscountValue.ForeColor = System.Drawing.Color.FromArgb(100, 116, 139) ' Slate Gray
        End If

        lblTotalValue.Text = String.Format("Rs. {0:N2}", totalPayable)

        ' Display success message summary
        MessageBox.Show(String.Format("Bill calculated successfully for {0}!" & vbCrLf & vbCrLf &
                                      "Subtotal: Rs. {1:N2}" & vbCrLf &
                                      "Discount: Rs. {2:N2}" & vbCrLf &
                                      "Total Payable: Rs. {3:N2}",
                                      txtPatientName.Text.Trim(), subtotal, discountAmount, totalPayable),
                        "Bill Summary", MessageBoxButtons.OK, MessageBoxIcon.Information)
    End Sub

    ''' <summary>
    ''' Event Handler: Reset Form
    ''' Clears all inputs and resets form to initial state
    ''' </summary>
    Private Sub btnClear_Click(sender As Object, e As EventArgs) Handles btnClear.Click
        txtPatientName.Clear()
        txtConsultationFee.Clear()
        txtDaysAdmitted.Text = "0"
        txtDailyRoomCharge.Text = "1000"
        rdoNotInsured.Checked = True
        lblSubtotalValue.Text = "Rs. 0.00"
        lblDiscountValue.Text = "- Rs. 0.00"
        lblDiscountValue.ForeColor = System.Drawing.Color.FromArgb(5, 150, 105)
        lblTotalValue.Text = "Rs. 0.00"
        txtPatientName.Focus()
    End Sub

End Class
