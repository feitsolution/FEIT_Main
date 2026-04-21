import os
from typing import Any

import pandas as pd
from flask import flash

from adms_wrapper.controllers.data_processing_controller import process_attendance_summary
from adms_wrapper.controllers.excel_logic_controller import generate_attendance_summary
from adms_wrapper.models.db_queries_models import (
    add_device_branch_mapping,
    add_employee_branch_mapping,
    add_employee_designation_mapping,
    add_employee_name_mapping,
    add_user_shift_mapping,
    delete_device_branch_mapping,
    delete_employee_branch_mapping,
    delete_employee_designation_mapping,
    delete_employee_name_mapping,
    delete_user_shift_mapping,
    get_device_branch_mappings,
    get_device_logs,
    get_employee_branch_mappings,
    get_employee_designation_mappings,
    get_employee_name_mappings,
    get_finger_log,
    get_migrations,
    get_users,
)


def handle_shift_mapping_deletion(delete_user_id: str) -> None:
    """Handle deletion of user shift mapping."""
    delete_user_shift_mapping(delete_user_id)
    flash(f"Shift mapping deleted: {delete_user_id}", "success")


def handle_shift_mapping_addition(user_id: str, shift_name: str, shift_start: str, shift_end: str) -> None:
    """Handle addition of user shift mapping."""
    if user_id and shift_name and shift_start and shift_end:
        add_user_shift_mapping(user_id, shift_name, shift_start, shift_end)
        flash(f"Shift mapping added: {user_id} → {shift_name}", "success")
    else:
        flash("All fields are required.", "error")


def handle_device_mapping_deletion(delete_sn: str) -> None:
    """Handle deletion of device branch mapping."""
    delete_device_branch_mapping(delete_sn)
    flash(f"Mapping deleted: {delete_sn}", "success")


def handle_device_mapping_addition(serial_number: str, branch_name: str) -> None:
    """Handle addition of device branch mapping."""
    if serial_number and branch_name:
        add_device_branch_mapping(serial_number, branch_name)
        flash(f"Mapping added: {serial_number} → {branch_name}", "success")
    else:
        flash("Both serial number and branch name are required.", "error")


def handle_designation_mapping_deletion(delete_emp_id: str) -> None:
    """Handle deletion of employee designation mapping."""
    delete_employee_designation_mapping(delete_emp_id)
    flash(f"Designation mapping deleted: {delete_emp_id}", "success")


def handle_designation_mapping_addition(employee_id: str, designation: str) -> None:
    """Handle addition of employee designation mapping."""
    if employee_id and designation:
        add_employee_designation_mapping(employee_id, designation)
        flash(f"Designation mapping added: {employee_id} → {designation}", "success")
    else:
        flash("Both employee ID and designation are required.", "error")


def handle_employee_name_deletion(delete_emp_id: str) -> None:
    """Handle deletion of employee name mapping."""
    delete_employee_name_mapping(delete_emp_id)
    flash(f"Name mapping deleted: {delete_emp_id}", "success")


def handle_employee_name_addition(employee_id: str, employee_name: str) -> None:
    """Handle addition of employee name mapping."""
    if employee_id and employee_name:
        add_employee_name_mapping(employee_id, employee_name)
        flash(f"Name mapping added: {employee_id} → {employee_name}", "success")
    else:
        flash("Both employee ID and employee name are required.", "error")


def handle_employee_branch_deletion(delete_emp_id: str) -> None:
    """Handle deletion of employee branch mapping."""
    delete_employee_branch_mapping(delete_emp_id)
    flash(f"Branch mapping deleted: {delete_emp_id}", "success")


def handle_employee_branch_addition(employee_id: str, branch_name: str) -> None:
    """Handle addition of employee branch mapping."""
    if employee_id and branch_name:
        add_employee_branch_mapping(employee_id, branch_name)
        flash(f"Branch mapping added: {employee_id} → {branch_name}", "success")
    else:
        flash("Both employee ID and branch name are required.", "error")


def ensure_directories_exist() -> None:
    """Ensure the static and templates folders exist."""
    if not os.path.exists("static"):
        os.makedirs("static")
    if not os.path.exists("templates"):
        os.makedirs("templates")


def filter_attendances_by_date(df: pd.DataFrame, start_date: str | None, end_date: str | None) -> pd.DataFrame:
    """Filter attendances by date range."""
    if "timestamp" in df.columns:
        df["timestamp"] = pd.to_datetime(df["timestamp"])
        if start_date:
            # Start date should include from 00:00:00 of that day
            start_datetime = pd.to_datetime(start_date).replace(hour=0, minute=0, second=0, microsecond=0)
            df = df[df["timestamp"] >= start_datetime]
        if end_date:
            # End date should include until 23:59:59 of that day
            end_datetime = pd.to_datetime(end_date).replace(hour=23, minute=59, second=59, microsecond=999999)
            df = df[df["timestamp"] <= end_datetime]
    return df


def filter_attendances_by_employee(df: pd.DataFrame, employee_id: str) -> pd.DataFrame:
    """Filter attendances by employee ID."""
    return df[df["employee_id"].astype(str).str.contains(str(employee_id), case=False, na=False)]


def filter_attendances_by_branch(df: pd.DataFrame, branch_name: str) -> pd.DataFrame:
    """Filter attendances by branch name."""
    branch_mappings = get_device_branch_mappings() or []
    branch_serials = [b["serial_number"] for b in branch_mappings if branch_name.lower() in b["branch_name"].lower()]
    if branch_serials:
        df = df[df["sn"].isin(branch_serials)]
    return df


def filter_attendances_by_employee_branch(df: pd.DataFrame, employee_branch: str) -> pd.DataFrame:
    """Filter attendances by employee branch."""
    employee_branch_mappings = get_employee_branch_mappings() or []
    branch_employees = [str(eb["employee_id"]) for eb in employee_branch_mappings if employee_branch.lower() in eb["branch_name"].lower()]
    if branch_employees:
        df = df[df["employee_id"].astype(str).isin(branch_employees)]
    return df


def filter_attendances_by_employee_name(df: pd.DataFrame, employee_name: str) -> pd.DataFrame:
    """Filter attendances by employee name."""
    employee_name_mappings = get_employee_name_mappings() or []
    name_employees = [str(en["employee_id"]) for en in employee_name_mappings if employee_name.lower() in en["employee_name"].lower()]
    if name_employees:
        df = df[df["employee_id"].astype(str).isin(name_employees)]
    return df


def filter_attendances_by_designation(df: pd.DataFrame, designation: str) -> pd.DataFrame:
    """Filter attendances by designation."""
    employee_designation_mappings = get_employee_designation_mappings() or []
    designation_employees = [str(ed["employee_id"]) for ed in employee_designation_mappings if designation.lower() in ed["designation"].lower()]
    if designation_employees:
        df = df[df["employee_id"].astype(str).isin(designation_employees)]
    return df


def apply_filters(
    attendences: list[dict[str, Any]],
    start_date: str | None,
    end_date: str | None,
    employee_id: str | None,
    branch_name: str | None,
    employee_branch: str | None,
    employee_name: str | None = None,
    designation: str | None = None,
) -> list[dict[str, Any]]:
    """Apply all filters to attendances."""
    if not (start_date or end_date or employee_id or branch_name or employee_branch or employee_name or designation):
        return attendences

    df = pd.DataFrame(attendences)

    df = filter_attendances_by_date(df, start_date, end_date)

    if employee_id:
        df = filter_attendances_by_employee(df, employee_id)

    if branch_name:
        df = filter_attendances_by_branch(df, branch_name)

    if employee_branch:
        df = filter_attendances_by_employee_branch(df, employee_branch)

    if employee_name:
        df = filter_attendances_by_employee_name(df, employee_name)

    if designation:
        df = filter_attendances_by_designation(df, designation)

    return df.to_dict(orient="records")


def prepare_dashboard_summary(
    attendences: list[dict[str, Any]],
    shift_mappings: list[dict[str, Any]],
    start_date: str | None = None,
    end_date: str | None = None,
    branch_name: str | None = None,
    employee_branch: str | None = None,
    employee_name: str | None = None,
    designation: str | None = None,
) -> list[dict[str, Any]]:
    """Prepare summary data for dashboard, including all days."""
    summary_df = process_attendance_summary(attendences, start_date, end_date, branch_name, employee_branch, employee_name, designation)
    if summary_df is None or summary_df.empty:
        return []

    device_logs = get_device_logs() or []
    finger_logs = get_finger_log() or []
    migration_logs = get_migrations() or []
    user_logs = get_users() or []

    full_summary_df = generate_attendance_summary(
        attendences, device_logs, finger_logs, migration_logs, user_logs, shift_mappings, start_date, end_date, branch_name, employee_branch, employee_name, designation
    )

    if full_summary_df.empty or "work_status" not in full_summary_df.columns:
        return []

    dashboard_summary_df = full_summary_df[(full_summary_df["work_status"] == "worked") & (full_summary_df["day"] != "Subtotal")].copy()

    # Convert to list and include all days (including Sundays)
    dashboard_summary = dashboard_summary_df.to_dict(orient="records")

    return dashboard_summary


def add_branch_info_to_summary(summary: list[dict[str, Any]]) -> None:
    """Add branch information to summary records."""
    branch_mappings = get_device_branch_mappings() or []
    branch_map = {str(b["serial_number"]): b["branch_name"] for b in branch_mappings}

    for row in summary:
        row["start_device_sn_branch"] = branch_map.get(str(row.get("start_device_sn")), "")
        row["end_device_sn_branch"] = branch_map.get(str(row.get("end_device_sn")), "")


def add_employee_name_to_summary(summary: list[dict[str, Any]]) -> None:
    """Add employee name information to summary records."""
    name_mappings = get_employee_name_mappings() or []
    name_map = {str(n["employee_id"]): n["employee_name"] for n in name_mappings}

    for row in summary:
        row["employee_name"] = name_map.get(str(row.get("employee_id")), "")
