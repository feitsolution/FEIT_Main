import io
from datetime import datetime, timedelta
from typing import Any

import pandas as pd
from flask import Blueprint, flash, redirect, request, send_file, url_for

from adms_wrapper.controllers.excel_logic_controller import (
    apply_flag_highlighting,
    apply_row_highlighting,
    generate_attendance_summary,
    generate_branch_attendance_summary,
    write_branch_summary_csv,
    write_csv,
)
from adms_wrapper.controllers.frontend_logic_controller import (
    add_branch_info_to_summary,
    add_employee_name_to_summary,
    prepare_dashboard_summary,
)
from adms_wrapper.models.db_queries_models import (
    get_attendences_filtered,
    get_device_logs,
    get_employee_branch_mappings,
    get_employee_designation_mappings,
    get_employee_name_mappings,
    get_finger_log,
    get_migrations,
    get_user_shift_mappings,
    get_users,
)

# ==============================================================================
# Blueprint Definition
# ==============================================================================

router = Blueprint("reports", __name__)

# ==============================================================================
# Route Definitions
# ==============================================================================


@router.route("/download_xlsx")
def download_xlsx() -> Any:
    start_date = request.args.get("start_date")
    end_date = request.args.get("end_date")
    employee_id = request.args.get("employee_id")
    branch_name = request.args.get("branch_name")
    employee_branch = request.args.get("employee_branch")
    employee_name = request.args.get("employee_name")
    designation = request.args.get("designation")

    if not start_date or not end_date:
        flash("Both start date and end date are required to download the Excel file.", "error")
        return redirect(
            url_for(
                "dashboard.index",
                start_date=start_date or "",
                end_date=end_date or "",
                employee_id=employee_id or "",
                branch_name=branch_name or "",
                employee_branch=employee_branch or "",
                employee_name=employee_name or "",
                designation=designation or "",
            )
        )

    attendences = get_attendences_filtered(start_date, end_date, employee_id, branch_name, employee_branch, employee_name, designation) or []
    device_logs = get_device_logs() or []
    finger_logs = get_finger_log() or []
    migration_logs = get_migrations() or []
    user_logs = get_users() or []
    shift_mappings = get_user_shift_mappings() or []

    merged = generate_attendance_summary(attendences, device_logs, finger_logs, migration_logs, user_logs, shift_mappings, start_date, end_date)
    xlsx_output = write_csv(attendences, device_logs, finger_logs, migration_logs, user_logs, merged)

    current_date = datetime.now().strftime("%Y-%m-%d")
    if employee_branch:
        safe_branch_name = "".join(c if c.isalnum() or c in (' ', '_', '-') else '_' for c in employee_branch)
        safe_branch_name = safe_branch_name.replace(' ', '_')
        filename = f"{safe_branch_name}_{current_date}.xlsx"
    else:
        filename = f"attendance_summary_{current_date}.xlsx"

    return send_file(
        xlsx_output,
        as_attachment=True,
        download_name=filename,
        mimetype="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    )


@router.route("/download_filtered_attendance")
def download_filtered_attendance() -> Any:
    start_date = request.args.get("start_date")
    end_date = request.args.get("end_date")
    employee_id = request.args.get("employee_id")
    branch_name = request.args.get("branch_name")
    employee_branch = request.args.get("employee_branch")
    employee_name = request.args.get("employee_name")
    designation = request.args.get("designation")

    if not start_date or not end_date:
        flash("Both start date and end date are required to download the Excel file.", "error")
        return redirect(
            url_for(
                "dashboard.index",
                start_date=start_date or "",
                end_date=end_date or "",
                employee_id=employee_id or "",
                branch_name=branch_name or "",
                employee_branch=employee_branch or "",
                employee_name=employee_name or "",
                designation=designation or "",
            )
        )

    attendences = get_attendences_filtered(start_date, end_date, employee_id, branch_name, employee_branch, employee_name, designation) or []
    shift_mappings = get_user_shift_mappings() or []
    summary = prepare_dashboard_summary(attendences, shift_mappings, start_date, end_date, branch_name, employee_branch, employee_name, designation)
    add_branch_info_to_summary(summary)
    add_employee_name_to_summary(summary)

    start_dt = datetime.strptime(start_date, "%Y-%m-%d")
    end_dt = datetime.strptime(end_date, "%Y-%m-%d")

    all_dates = []
    current_date = start_dt
    while current_date <= end_dt:
        all_dates.append(current_date.strftime("%Y-%m-%d"))
        current_date += timedelta(days=1)

    if employee_id:
        all_employees = [{"employee_id": employee_id}]
    else:
        all_employees = []
        seen_employees = set()
        for record in attendences:
            emp_id = str(record.get("employee_id", ""))
            if emp_id and emp_id not in seen_employees:
                all_employees.append({"employee_id": emp_id})
                seen_employees.add(emp_id)

    name_mappings = get_employee_name_mappings() or []
    name_map = {str(n["employee_id"]): n["employee_name"] for n in name_mappings}
    for emp in all_employees:
        emp["employee_name"] = name_map.get(str(emp["employee_id"]), "")

    if employee_name:
        all_employees = [emp for emp in all_employees if employee_name.lower() in emp["employee_name"].lower()]
    if designation:
        designation_mappings = get_employee_designation_mappings() or []
        designation_map = {str(d["employee_id"]): d["designation"] for d in designation_mappings}
        all_employees = [emp for emp in all_employees if designation.lower() in designation_map.get(str(emp["employee_id"]), "").lower()]
    if employee_branch:
        branch_mappings = get_employee_branch_mappings() or []
        branch_map = {str(b["employee_id"]): b["branch_name"] for b in branch_mappings}
        all_employees = [emp for emp in all_employees if employee_branch.lower() in branch_map.get(str(emp["employee_id"]), "").lower()]

    attendance_lookup = {f"{record.get('employee_id')}_{record.get('day')}": record for record in summary}

    filtered_data = []
    for employee in all_employees:
        emp_id = employee["employee_id"]
        emp_name = employee["employee_name"]
        for date in all_dates:
            key = f"{emp_id}_{date}"
            if key in attendance_lookup:
                record = attendance_lookup[key]
                filtered_data.append(
                    {
                        "Employee ID": emp_id,
                        "Employee Name": emp_name,
                        "Date": date,
                        "Time In": record.get("start_time"),
                        "Time Out": record.get("end_time"),
                        "Shift Flag": record.get("shift_flag", "normal"),
                    }
                )
            else:
                filtered_data.append(
                    {
                        "Employee ID": emp_id,
                        "Employee Name": emp_name,
                        "Date": date,
                        "Time In": None,
                        "Time Out": None,
                        "Shift Flag": "absent",
                    }
                )

    if not filtered_data:
        df = pd.DataFrame(columns=["Employee ID", "Employee Name", "Date", "Time In", "Time Out", "Shift Flag"])
    else:
        df = pd.DataFrame(filtered_data)

        def format_time_series(series):
            def formatter(x):
                if pd.isna(x) or x == "":
                    return "Absent"
                if hasattr(x, "strftime"):
                    return x.strftime("%H:%M:%S")
                return str(x)

            return series.apply(formatter)

        df["Time In"] = format_time_series(df["Time In"])
        df["Time Out"] = format_time_series(df["Time Out"])

        flag_map = {
            "shift_capped": "no checkout",
            "shift cap": "no checkout",
            "shiftcap": "no checkout",
            "latein": "late in",
            "earlyin": "early in",
            "earlyout": "early out",
            "over time": "overtime",
            "late-checkout": "late checkout",
            "latecheckout": "late checkout",
            "on time": "normal",
            "ontime": "normal",
        }
        df["Shift Flag"] = df["Shift Flag"].astype(str).str.strip().str.lower().replace(flag_map)

        if "Employee ID" in df.columns and "Date" in df.columns:
            df = df.sort_values(["Employee ID", "Date"])

    output = io.BytesIO()
    with pd.ExcelWriter(output, engine="openpyxl") as writer:
        df.to_excel(writer, index=False, sheet_name="Filtered Attendance")
        ws = writer.sheets["Filtered Attendance"]
        apply_flag_highlighting(ws)
    output.seek(0)

    download_name = f"filtered_attendance_{start_date}_to_{end_date}.xlsx"
    return send_file(
        output,
        as_attachment=True,
        download_name=download_name,
        mimetype="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    )


@router.route("/download_branch_summary")
def download_branch_summary() -> Any:
    start_date = request.args.get("start_date")
    end_date = request.args.get("end_date")
    branch_name = request.args.get("branch_name")
    employee_name = request.args.get("employee_name")
    designation = request.args.get("designation")

    if not start_date or not end_date:
        flash("Both start date and end date are required to download the branch summary.", "error")
        return redirect(url_for("dashboard.index"))
    if not branch_name or not branch_name.strip():
        flash("Branch name is required to download the branch summary.", "error")
        return redirect(url_for("dashboard.index"))

    employee_branch_mappings = get_employee_branch_mappings() or []
    branch_employees = [emp for emp in employee_branch_mappings if emp.get("branch_name", "").lower() == branch_name.lower()]

    if not branch_employees:
        flash(f"No employees found assigned to branch '{branch_name}'.", "error")
        return redirect(url_for("dashboard.index"))

    branch_employee_ids = [str(emp["employee_id"]) for emp in branch_employees]
    all_attendences = []
    for emp_id in branch_employee_ids:
        emp_attendences = get_attendences_filtered(start_date, end_date, emp_id, None, None, employee_name, designation) or []
        all_attendences.extend(emp_attendences)

    device_logs = get_device_logs() or []
    finger_logs = get_finger_log() or []
    migration_logs = get_migrations() or []
    user_logs = get_users() or []
    shift_mappings = get_user_shift_mappings() or []

    merged = generate_branch_attendance_summary(
        all_attendences, branch_employee_ids, device_logs, finger_logs, migration_logs, user_logs, shift_mappings, start_date, end_date
    )

    xlsx_output = write_branch_summary_csv(merged, branch_name)

    formatted_time = datetime.now().strftime("%Y-%m-%d_%H-%M-%S")
    filename = f"{branch_name}_{formatted_time}.xlsx"

    return send_file(
        xlsx_output,
        as_attachment=True,
        download_name=filename,
        mimetype="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    )