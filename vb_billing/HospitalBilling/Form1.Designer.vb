Imports System.Windows.Forms

<Global.Microsoft.VisualBasic.CompilerServices.DesignerGenerated()> _
Partial Class Form1
    Inherits System.Windows.Forms.Form

    'Form overrides dispose to clean up the component list.
    <System.Diagnostics.DebuggerNonUserCode()> _
    Protected Overrides Sub Dispose(ByVal disposing As Boolean)
        Try
            If disposing AndAlso components IsNot Nothing Then
                components.Dispose()
            End If
        Finally
            MyBase.Dispose(disposing)
        End Try
    End Sub

    Private components As System.ComponentModel.IContainer

    Friend WithEvents grpPatientInfo As System.Windows.Forms.GroupBox
    Friend WithEvents lblPatientName As System.Windows.Forms.Label
    Friend WithEvents txtPatientName As System.Windows.Forms.TextBox
    Friend WithEvents lblConsultationFee As System.Windows.Forms.Label
    Friend WithEvents txtConsultationFee As System.Windows.Forms.TextBox
    Friend WithEvents lblDaysAdmitted As System.Windows.Forms.Label
    Friend WithEvents txtDaysAdmitted As System.Windows.Forms.TextBox
    Friend WithEvents lblDailyRoomCharge As System.Windows.Forms.Label
    Friend WithEvents txtDailyRoomCharge As System.Windows.Forms.TextBox

    Friend WithEvents grpInsurance As System.Windows.Forms.GroupBox
    Friend WithEvents rdoInsured As System.Windows.Forms.RadioButton
    Friend WithEvents rdoNotInsured As System.Windows.Forms.RadioButton

    Friend WithEvents grpSummary As System.Windows.Forms.GroupBox
    Friend WithEvents lblSubtotalLabel As System.Windows.Forms.Label
    Friend WithEvents lblSubtotalValue As System.Windows.Forms.Label
    Friend WithEvents lblDiscountLabel As System.Windows.Forms.Label
    Friend WithEvents lblDiscountValue As System.Windows.Forms.Label
    Friend WithEvents lblTotalLabel As System.Windows.Forms.Label
    Friend WithEvents lblTotalValue As System.Windows.Forms.Label

    Friend WithEvents btnCalculate As System.Windows.Forms.Button
    Friend WithEvents btnClear As System.Windows.Forms.Button
    Friend WithEvents lblHeader As System.Windows.Forms.Label

    <System.Diagnostics.DebuggerStepThrough()> _
    Private Sub InitializeComponent()
        Me.lblHeader = New System.Windows.Forms.Label()
        Me.grpPatientInfo = New System.Windows.Forms.GroupBox()
        Me.lblPatientName = New System.Windows.Forms.Label()
        Me.txtPatientName = New System.Windows.Forms.TextBox()
        Me.lblConsultationFee = New System.Windows.Forms.Label()
        Me.txtConsultationFee = New System.Windows.Forms.TextBox()
        Me.lblDaysAdmitted = New System.Windows.Forms.Label()
        Me.txtDaysAdmitted = New System.Windows.Forms.TextBox()
        Me.lblDailyRoomCharge = New System.Windows.Forms.Label()
        Me.txtDailyRoomCharge = New System.Windows.Forms.TextBox()
        Me.grpInsurance = New System.Windows.Forms.GroupBox()
        Me.rdoInsured = New System.Windows.Forms.RadioButton()
        Me.rdoNotInsured = New System.Windows.Forms.RadioButton()
        Me.grpSummary = New System.Windows.Forms.GroupBox()
        Me.lblSubtotalLabel = New System.Windows.Forms.Label()
        Me.lblSubtotalValue = New System.Windows.Forms.Label()
        Me.lblDiscountLabel = New System.Windows.Forms.Label()
        Me.lblDiscountValue = New System.Windows.Forms.Label()
        Me.lblTotalLabel = New System.Windows.Forms.Label()
        Me.lblTotalValue = New System.Windows.Forms.Label()
        Me.btnCalculate = New System.Windows.Forms.Button()
        Me.btnClear = New System.Windows.Forms.Button()
        Me.grpPatientInfo.SuspendLayout()
        Me.grpInsurance.SuspendLayout()
        Me.grpSummary.SuspendLayout()
        Me.SuspendLayout()
        '
        ' lblHeader
        '
        Me.lblHeader.BackColor = System.Drawing.Color.FromArgb(CType(CType(37, Byte), Integer), CType(CType(99, Byte), Integer), CType(CType(235, Byte), Integer))
        Me.lblHeader.Dock = System.Windows.Forms.DockStyle.Top
        Me.lblHeader.Font = New System.Drawing.Font("Segoe UI", 16.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.lblHeader.ForeColor = System.Drawing.Color.White
        Me.lblHeader.Location = New System.Drawing.Point(0, 0)
        Me.lblHeader.Name = "lblHeader"
        Me.lblHeader.Size = New System.Drawing.Size(620, 60)
        Me.lblHeader.TabIndex = 0
        Me.lblHeader.Text = "🏥 Smart Healthcare — Mini Hospital Billing"
        Me.lblHeader.TextAlign = System.Drawing.ContentAlignment.MiddleCenter
        '
        ' grpPatientInfo
        '
        Me.grpPatientInfo.Controls.Add(Me.lblPatientName)
        Me.grpPatientInfo.Controls.Add(Me.txtPatientName)
        Me.grpPatientInfo.Controls.Add(Me.lblConsultationFee)
        Me.grpPatientInfo.Controls.Add(Me.txtConsultationFee)
        Me.grpPatientInfo.Controls.Add(Me.lblDaysAdmitted)
        Me.grpPatientInfo.Controls.Add(Me.txtDaysAdmitted)
        Me.grpPatientInfo.Controls.Add(Me.lblDailyRoomCharge)
        Me.grpPatientInfo.Controls.Add(Me.txtDailyRoomCharge)
        Me.grpPatientInfo.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.grpPatientInfo.ForeColor = System.Drawing.Color.FromArgb(CType(CType(30, Byte), Integer), CType(CType(41, Byte), Integer), CType(CType(59, Byte), Integer))
        Me.grpPatientInfo.Location = New System.Drawing.Point(24, 75)
        Me.grpPatientInfo.Name = "grpPatientInfo"
        Me.grpPatientInfo.Size = New System.Drawing.Size(572, 190)
        Me.grpPatientInfo.TabIndex = 1
        Me.grpPatientInfo.TabStop = False
        Me.grpPatientInfo.Text = "📋 Patient & Admission Details"
        '
        ' lblPatientName
        '
        Me.lblPatientName.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.lblPatientName.Location = New System.Drawing.Point(20, 35)
        Me.lblPatientName.Name = "lblPatientName"
        Me.lblPatientName.Size = New System.Drawing.Size(160, 25)
        Me.lblPatientName.TabIndex = 0
        Me.lblPatientName.Text = "Patient Full Name:"
        '
        ' txtPatientName
        '
        Me.txtPatientName.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.txtPatientName.Location = New System.Drawing.Point(185, 32)
        Me.txtPatientName.Name = "txtPatientName"
        Me.txtPatientName.Size = New System.Drawing.Size(360, 30)
        Me.txtPatientName.TabIndex = 1
        '
        ' lblConsultationFee
        '
        Me.lblConsultationFee.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.lblConsultationFee.Location = New System.Drawing.Point(20, 75)
        Me.lblConsultationFee.Name = "lblConsultationFee"
        Me.lblConsultationFee.Size = New System.Drawing.Size(160, 25)
        Me.lblConsultationFee.TabIndex = 2
        Me.lblConsultationFee.Text = "Consultation Fee (Rs.):"
        '
        ' txtConsultationFee
        '
        Me.txtConsultationFee.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.txtConsultationFee.Location = New System.Drawing.Point(185, 72)
        Me.txtConsultationFee.Name = "txtConsultationFee"
        Me.txtConsultationFee.Size = New System.Drawing.Size(360, 30)
        Me.txtConsultationFee.TabIndex = 3
        '
        ' lblDaysAdmitted
        '
        Me.lblDaysAdmitted.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.lblDaysAdmitted.Location = New System.Drawing.Point(20, 115)
        Me.lblDaysAdmitted.Name = "lblDaysAdmitted"
        Me.lblDaysAdmitted.Size = New System.Drawing.Size(160, 25)
        Me.lblDaysAdmitted.TabIndex = 4
        Me.lblDaysAdmitted.Text = "Days Admitted:"
        '
        ' txtDaysAdmitted
        '
        Me.txtDaysAdmitted.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.txtDaysAdmitted.Location = New System.Drawing.Point(185, 112)
        Me.txtDaysAdmitted.Name = "txtDaysAdmitted"
        Me.txtDaysAdmitted.Size = New System.Drawing.Size(100, 30)
        Me.txtDaysAdmitted.TabIndex = 5
        Me.txtDaysAdmitted.Text = "0"
        '
        ' lblDailyRoomCharge
        '
        Me.lblDailyRoomCharge.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.lblDailyRoomCharge.Location = New System.Drawing.Point(300, 115)
        Me.lblDailyRoomCharge.Name = "lblDailyRoomCharge"
        Me.lblDailyRoomCharge.Size = New System.Drawing.Size(130, 25)
        Me.lblDailyRoomCharge.TabIndex = 6
        Me.lblDailyRoomCharge.Text = "Room Charge/Day:"
        '
        ' txtDailyRoomCharge
        '
        Me.txtDailyRoomCharge.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.txtDailyRoomCharge.Location = New System.Drawing.Point(435, 112)
        Me.txtDailyRoomCharge.Name = "txtDailyRoomCharge"
        Me.txtDailyRoomCharge.Size = New System.Drawing.Size(110, 30)
        Me.txtDailyRoomCharge.TabIndex = 7
        Me.txtDailyRoomCharge.Text = "1000"
        '
        ' grpInsurance
        '
        Me.grpInsurance.Controls.Add(Me.rdoInsured)
        Me.grpInsurance.Controls.Add(Me.rdoNotInsured)
        Me.grpInsurance.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.grpInsurance.ForeColor = System.Drawing.Color.FromArgb(CType(CType(30, Byte), Integer), CType(CType(41, Byte), Integer), CType(CType(59, Byte), Integer))
        Me.grpInsurance.Location = New System.Drawing.Point(24, 275)
        Me.grpInsurance.Name = "grpInsurance"
        Me.grpInsurance.Size = New System.Drawing.Size(572, 75)
        Me.grpInsurance.TabIndex = 2
        Me.grpInsurance.TabStop = False
        Me.grpInsurance.Text = "🛡️ Insurance Discount Policy"
        '
        ' rdoInsured
        '
        Me.rdoInsured.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.rdoInsured.Location = New System.Drawing.Point(50, 30)
        Me.rdoInsured.Name = "rdoInsured"
        Me.rdoInsured.Size = New System.Drawing.Size(220, 30)
        Me.rdoInsured.TabIndex = 0
        Me.rdoInsured.Text = "Insured (20% Discount)"
        Me.rdoInsured.UseVisualStyleBackColor = True
        '
        ' rdoNotInsured
        '
        Me.rdoNotInsured.Checked = True
        Me.rdoNotInsured.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.rdoNotInsured.Location = New System.Drawing.Point(300, 30)
        Me.rdoNotInsured.Name = "rdoNotInsured"
        Me.rdoNotInsured.Size = New System.Drawing.Size(220, 30)
        Me.rdoNotInsured.TabIndex = 1
        Me.rdoNotInsured.TabStop = True
        Me.rdoNotInsured.Text = "Not Insured (0% Discount)"
        Me.rdoNotInsured.UseVisualStyleBackColor = True
        '
        ' btnCalculate
        '
        Me.btnCalculate.BackColor = System.Drawing.Color.FromArgb(CType(CType(37, Byte), Integer), CType(CType(99, Byte), Integer), CType(CType(235, Byte), Integer))
        Me.btnCalculate.FlatStyle = System.Windows.Forms.FlatStyle.Flat
        Me.btnCalculate.Font = New System.Drawing.Font("Segoe UI", 10.5!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.btnCalculate.ForeColor = System.Drawing.Color.White
        Me.btnCalculate.Location = New System.Drawing.Point(24, 360)
        Me.btnCalculate.Name = "btnCalculate"
        Me.btnCalculate.Size = New System.Drawing.Size(360, 45)
        Me.btnCalculate.TabIndex = 3
        Me.btnCalculate.Text = "💰 Calculate Total Hospital Bill"
        Me.btnCalculate.UseVisualStyleBackColor = False
        '
        ' btnClear
        '
        Me.btnClear.BackColor = System.Drawing.Color.FromArgb(CType(CType(100, Byte), Integer), CType(CType(116, Byte), Integer), CType(CType(139, Byte), Integer))
        Me.btnClear.FlatStyle = System.Windows.Forms.FlatStyle.Flat
        Me.btnClear.Font = New System.Drawing.Font("Segoe UI", 10.5!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.btnClear.ForeColor = System.Drawing.Color.White
        Me.btnClear.Location = New System.Drawing.Point(400, 360)
        Me.btnClear.Name = "btnClear"
        Me.btnClear.Size = New System.Drawing.Size(196, 45)
        Me.btnClear.TabIndex = 4
        Me.btnClear.Text = "🔄 Reset Form"
        Me.btnClear.UseVisualStyleBackColor = False
        '
        ' grpSummary
        '
        Me.grpSummary.Controls.Add(Me.lblSubtotalLabel)
        Me.grpSummary.Controls.Add(Me.lblSubtotalValue)
        Me.grpSummary.Controls.Add(Me.lblDiscountLabel)
        Me.grpSummary.Controls.Add(Me.lblDiscountValue)
        Me.grpSummary.Controls.Add(Me.lblTotalLabel)
        Me.grpSummary.Controls.Add(Me.lblTotalValue)
        Me.grpSummary.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.grpSummary.ForeColor = System.Drawing.Color.FromArgb(CType(CType(30, Byte), Integer), CType(CType(41, Byte), Integer), CType(CType(59, Byte), Integer))
        Me.grpSummary.Location = New System.Drawing.Point(24, 420)
        Me.grpSummary.Name = "grpSummary"
        Me.grpSummary.Size = New System.Drawing.Size(572, 160)
        Me.grpSummary.TabIndex = 5
        Me.grpSummary.TabStop = False
        Me.grpSummary.Text = "🧾 Bill Calculation Summary"
        '
        ' lblSubtotalLabel
        '
        Me.lblSubtotalLabel.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.lblSubtotalLabel.Location = New System.Drawing.Point(30, 35)
        Me.lblSubtotalLabel.Name = "lblSubtotalLabel"
        Me.lblSubtotalLabel.Size = New System.Drawing.Size(200, 25)
        Me.lblSubtotalLabel.Text = "Subtotal Amount:"
        '
        ' lblSubtotalValue
        '
        Me.lblSubtotalValue.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.lblSubtotalValue.Location = New System.Drawing.Point(300, 35)
        Me.lblSubtotalValue.Name = "lblSubtotalValue"
        Me.lblSubtotalValue.Size = New System.Drawing.Size(240, 25)
        Me.lblSubtotalValue.Text = "Rs. 0.00"
        Me.lblSubtotalValue.TextAlign = System.Drawing.ContentAlignment.TopRight
        '
        ' lblDiscountLabel
        '
        Me.lblDiscountLabel.Font = New System.Drawing.Font("Segoe UI", 9.5!, System.Drawing.FontStyle.Regular, System.Drawing.GraphicsUnit.Point)
        Me.lblDiscountLabel.Location = New System.Drawing.Point(30, 70)
        Me.lblDiscountLabel.Name = "lblDiscountLabel"
        Me.lblDiscountLabel.Size = New System.Drawing.Size(200, 25)
        Me.lblDiscountLabel.Text = "Insurance Discount (20%):"
        '
        ' lblDiscountValue
        '
        Me.lblDiscountValue.Font = New System.Drawing.Font("Segoe UI", 10.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.lblDiscountValue.ForeColor = System.Drawing.Color.FromArgb(CType(CType(5, Byte), Integer), CType(CType(150, Byte), Integer), CType(CType(105, Byte), Integer))
        Me.lblDiscountValue.Location = New System.Drawing.Point(300, 70)
        Me.lblDiscountValue.Name = "lblDiscountValue"
        Me.lblDiscountValue.Size = New System.Drawing.Size(240, 25)
        Me.lblDiscountValue.Text = "- Rs. 0.00"
        Me.lblDiscountValue.TextAlign = System.Drawing.ContentAlignment.TopRight
        '
        ' lblTotalLabel
        '
        Me.lblTotalLabel.Font = New System.Drawing.Font("Segoe UI", 11.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.lblTotalLabel.Location = New System.Drawing.Point(30, 110)
        Me.lblTotalLabel.Name = "lblTotalLabel"
        Me.lblTotalLabel.Size = New System.Drawing.Size(200, 30)
        Me.lblTotalLabel.Text = "FINAL TOTAL PAYABLE:"
        '
        ' lblTotalValue
        '
        Me.lblTotalValue.Font = New System.Drawing.Font("Segoe UI", 12.0!, System.Drawing.FontStyle.Bold, System.Drawing.GraphicsUnit.Point)
        Me.lblTotalValue.ForeColor = System.Drawing.Color.FromArgb(CType(CType(29, Byte), Integer), CType(CType(78, Byte), Integer), CType(CType(216, Byte), Integer))
        Me.lblTotalValue.Location = New System.Drawing.Point(300, 108)
        Me.lblTotalValue.Name = "lblTotalValue"
        Me.lblTotalValue.Size = New System.Drawing.Size(240, 30)
        Me.lblTotalValue.Text = "Rs. 0.00"
        Me.lblTotalValue.TextAlign = System.Drawing.ContentAlignment.TopRight
        '
        ' Form1
        '
        Me.AutoScaleDimensions = New System.Drawing.SizeF(8.0!, 20.0!)
        Me.AutoScaleMode = System.Windows.Forms.AutoScaleMode.Font
        Me.BackColor = System.Drawing.Color.FromArgb(CType(CType(248, Byte), Integer), CType(CType(250, Byte), Integer), CType(CType(252, Byte), Integer))
        Me.ClientSize = New System.Drawing.Size(620, 600)
        Me.Controls.Add(Me.lblHeader)
        Me.Controls.Add(Me.grpPatientInfo)
        Me.Controls.Add(Me.grpInsurance)
        Me.Controls.Add(Me.btnCalculate)
        Me.Controls.Add(Me.btnClear)
        Me.Controls.Add(Me.grpSummary)
        Me.FormBorderStyle = System.Windows.Forms.FormBorderStyle.FixedSingle
        Me.MaximizeBox = False
        Me.Name = "Form1"
        Me.StartPosition = System.Windows.Forms.FormStartPosition.CenterScreen
        Me.Text = "Smart Healthcare — Mini Hospital Billing"
        Me.grpPatientInfo.ResumeLayout(False)
        Me.grpPatientInfo.PerformLayout()
        Me.grpInsurance.ResumeLayout(False)
        Me.grpSummary.ResumeLayout(False)
        Me.ResumeLayout(False)

    End Sub
End Class
