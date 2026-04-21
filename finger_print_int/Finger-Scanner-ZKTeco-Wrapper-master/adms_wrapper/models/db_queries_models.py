# --- Database Initialization ---

# Import the database query function from your connector
from adms_wrapper.tools.db_tools import query_db


def initialize_database():
    """
    Creates all necessary tables with indexes and default settings.
    This function should be run once at application startup to avoid
    redundant 'CREATE TABLE' checks on every database operation.
    """
    # Settings Table
    query_db("""
    CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) NOT NULL UNIQUE,
        setting_value VARCHAR(255) NOT NULL,
        description VARCHAR(500),
        INDEX idx_setting_key (setting_key)
    )
    """)
    # Insert all default settings in a single, efficient query
    query_db("""
    INSERT IGNORE INTO settings (setting_key, setting_value, description) VALUES
    ('default_shift', '', 'Default shift assigned to employees without a specific shift'),
    ('shift_cap_hours', '8', 'Hours after shift end to consider no-checkout / shift capped'),
    ('early_checkin_minutes', '30', 'Minutes before shift start to treat check-in as early in'),
    ('late_checkout_grace_minutes', '15', 'Minutes after shift end before considering a checkout as late'),
    ('shift_cap_type', 'zero', 'How to handle shift capping: "zero" zeroes work hours, "normal" calculates normally')
    """)

    # Shift Template Table
    query_db("""
    CREATE TABLE IF NOT EXISTS shift_template (
        id INT AUTO_INCREMENT PRIMARY KEY,
        shift_name VARCHAR(255) NOT NULL UNIQUE,
        shift_start TIME NOT NULL,
        shift_end TIME NOT NULL,
        description VARCHAR(500)
    )
    """)

    # User Shift Mapping Table
    query_db("""
    CREATE TABLE IF NOT EXISTS user_shift_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(255) NOT NULL UNIQUE,
        shift_name VARCHAR(255) NOT NULL,
        shift_start TIME NOT NULL,
        shift_end TIME NOT NULL,
        INDEX idx_user_id (user_id)
    )
    """)

    # Employee to Branch Mapping Table
    query_db("""
    CREATE TABLE IF NOT EXISTS employee_branch_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(255) NOT NULL UNIQUE,
        branch_name VARCHAR(255) NOT NULL,
        INDEX idx_employee_id (employee_id)
    )
    """)

    # Device Serial to Branch Mapping Table
    query_db("""
    CREATE TABLE IF NOT EXISTS branch_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        serial_number VARCHAR(255) NOT NULL UNIQUE,
        branch_name VARCHAR(255) NOT NULL
    )
    """)

    # Employee to Designation Mapping Table
    query_db("""
    CREATE TABLE IF NOT EXISTS employee_designation_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(255) NOT NULL UNIQUE,
        designation VARCHAR(255) NOT NULL,
        INDEX idx_employee_id (employee_id)
    )
    """)

    # Employee ID to Name Mapping Table
    query_db("""
    CREATE TABLE IF NOT EXISTS employee_name_mapping (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(255) NOT NULL UNIQUE,
        employee_name VARCHAR(255) NOT NULL,
        INDEX idx_employee_id (employee_id),
        UNIQUE INDEX idx_employee_name (employee_name)
    )
    """)


# --- Settings Management ---


def get_setting(setting_key: str) -> str:
    """Get a setting value by key."""
    result = query_db("SELECT setting_value FROM settings WHERE setting_key = %s", (setting_key,))
    return result[0]["setting_value"] if result else ""


def set_setting(setting_key: str, setting_value: str, description: str = ""):
    """Set a setting value using an atomic upsert operation."""
    query = """
    INSERT INTO settings (setting_key, setting_value, description)
    VALUES (%s, %s, %s)
    ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), description=VALUES(description)
    """
    return query_db(query, (setting_key, setting_value, description))


def get_default_shift() -> str:
    """Get the default shift name."""
    return get_setting("default_shift")


def set_default_shift(shift_name: str):
    """Set the default shift name."""
    return set_setting("default_shift", shift_name, "Default shift assigned to employees without a specific shift")


# --- Shift Template Management ---


def get_shift_templates() -> list:
    """Get all shift templates."""
    result = query_db("SELECT shift_name, shift_start, shift_end, description FROM shift_template ORDER BY shift_name")
    # Convert time objects to strings for compatibility
    return [{"shift_name": row["shift_name"], "shift_start": str(row["shift_start"]), "shift_end": str(row["shift_end"]), "description": row["description"]} for row in result or []]


def add_shift_template(shift_name: str, shift_start: str, shift_end: str, description: str = ""):
    """
    Add a shift template. Raises an error if the shift name already exists.
    Note: This relies on the database's UNIQUE constraint to prevent duplicates.
    The calling code should handle potential integrity errors from the database.
    """
    query = """
    INSERT INTO shift_template (shift_name, shift_start, shift_end, description)
    VALUES (%s, %s, %s, %s)
    """
    return query_db(query, (shift_name, shift_start, shift_end, description))


def delete_shift_template(shift_name: str):
    """Delete a shift template."""
    return query_db("DELETE FROM shift_template WHERE shift_name = %s", (shift_name,))


# --- User Shift Mapping ---


def get_user_shift_mappings() -> list:
    """Get all user shift mappings."""
    return query_db("SELECT user_id, shift_name, shift_start, shift_end FROM user_shift_mapping")


def add_user_shift_mapping(user_id: str, shift_name: str, shift_start: str, shift_end: str):
    """Add or update a user shift mapping using an atomic upsert."""
    query = """
    INSERT INTO user_shift_mapping (user_id, shift_name, shift_start, shift_end)
    VALUES (%s, %s, %s, %s)
    ON DUPLICATE KEY UPDATE 
        shift_name=VALUES(shift_name), 
        shift_start=VALUES(shift_start), 
        shift_end=VALUES(shift_end)
    """
    return query_db(query, (user_id, shift_name, shift_start, shift_end))


def assign_shift_template_to_user(user_id: str, shift_name: str):
    """Assign a shift template to a user by copying the template times."""
    template_result = query_db("SELECT shift_start, shift_end FROM shift_template WHERE shift_name = %s", (shift_name,))
    if not template_result:
        raise ValueError(f"Shift template '{shift_name}' not found")

    shift_start = template_result[0]["shift_start"]
    shift_end = template_result[0]["shift_end"]
    return add_user_shift_mapping(user_id, shift_name, str(shift_start), str(shift_end))


def delete_user_shift_mapping(user_id: str):
    """Delete a user shift mapping."""
    return query_db("DELETE FROM user_shift_mapping WHERE user_id = %s", (user_id,))


# --- Employee to Branch Mapping ---


def get_employee_branch_mappings() -> list:
    """Get all employee branch mappings."""
    return query_db("SELECT employee_id, branch_name FROM employee_branch_mapping")


def add_employee_branch_mapping(employee_id: str, branch_name: str):
    """Add or update an employee branch mapping using an atomic upsert."""
    query = """
    INSERT INTO employee_branch_mapping (employee_id, branch_name)
    VALUES (%s, %s)
    ON DUPLICATE KEY UPDATE branch_name=VALUES(branch_name)
    """
    return query_db(query, (employee_id, branch_name))


def delete_employee_branch_mapping(employee_id: str):
    """Delete an employee branch mapping."""
    return query_db("DELETE FROM employee_branch_mapping WHERE employee_id = %s", (employee_id,))


# --- Attendance Data ---


def _build_attendance_filter_query_parts(
    start_date: str | None, end_date: str | None, employee_id: str | None, branch_name: str | None, employee_branch: str | None, employee_name: str | None, designation: str | None
) -> tuple[str, str, tuple]:
    """Helper to build common SQL parts for attendance filtering to avoid code duplication."""
    joins, wheres, params = [], [], []

    if branch_name:
        joins.append("LEFT JOIN branch_mapping bm ON bm.serial_number = a.sn")
        wheres.append("bm.branch_name LIKE %s")
        params.append(f"%{branch_name}%")
    if employee_branch:
        joins.append("LEFT JOIN employee_branch_mapping eb ON eb.employee_id = a.employee_id")
        wheres.append("eb.branch_name LIKE %s")
        params.append(f"%{employee_branch}%")
    if employee_name:
        joins.append("LEFT JOIN employee_name_mapping en ON en.employee_id = a.employee_id")
        wheres.append("en.employee_name LIKE %s")
        params.append(f"%{employee_name}%")
    if designation:
        joins.append("LEFT JOIN employee_designation_mapping ed ON ed.employee_id = a.employee_id")
        wheres.append("ed.designation LIKE %s")
        params.append(f"%{designation}%")
    if start_date:
        wheres.append("a.`timestamp` >= %s")
        params.append(f"{start_date} 00:00:00")
    if end_date:
        wheres.append("a.`timestamp` <= %s")
        params.append(f"{end_date} 23:59:59")
    if employee_id:
        wheres.append("CAST(a.employee_id AS CHAR) LIKE %s")
        params.append(f"%{employee_id}%")

    join_clause = "\n".join(joins)
    where_clause = "WHERE " + " AND ".join(wheres) if wheres else ""
    return join_clause, where_clause, tuple(params)


def get_attendences_filtered(
    start_date: str | None = None,
    end_date: str | None = None,
    employee_id: str | None = None,
    branch_name: str | None = None,
    employee_branch: str | None = None,
    employee_name: str | None = None,
    designation: str | None = None,
    limit: int | None = None,
    offset: int | None = None,
):
    """Get filtered attendances using an efficient, single SQL query."""
    join_clause, where_clause, params = _build_attendance_filter_query_parts(start_date, end_date, employee_id, branch_name, employee_branch, employee_name, designation)

    limit_clause = ""
    if limit is not None:
        if offset is not None and offset > 0:
            limit_clause = "LIMIT %s OFFSET %s"
            params += (int(limit), int(offset))
        else:
            limit_clause = "LIMIT %s"
            params += (int(limit),)

    sql = f"""
    SELECT a.*
    FROM attendances a
    {join_clause}
    {where_clause}
    ORDER BY a.`timestamp` DESC, a.employee_id
    {limit_clause}
    """
    return query_db(sql, params)


def get_attendences_filtered_count(
    start_date: str | None = None,
    end_date: str | None = None,
    employee_id: str | None = None,
    branch_name: str | None = None,
    employee_branch: str | None = None,
    employee_name: str | None = None,
    designation: str | None = None,
) -> int:
    """Return count of attendances matching filters using a single SQL query."""
    join_clause, where_clause, params = _build_attendance_filter_query_parts(start_date, end_date, employee_id, branch_name, employee_branch, employee_name, designation)

    sql = f"""
    SELECT COUNT(*) AS cnt
    FROM attendances a
    {join_clause}
    {where_clause}
    """
    res = query_db(sql, params)
    return int(res[0]["cnt"]) if res else 0


# --- Unchanged Simple Data Retrieval Functions ---


def get_attendences() -> list:
    """Gets the attendence related data"""
    return query_db("select * from attendances a ")


def get_device_logs() -> list:
    """Gets the device log history."""
    return query_db("select * from device_log a ")


def get_finger_log() -> list:
    """Gets a log of all the available fingers registered in the system."""
    return query_db("select * from finger_log a ")


def get_migrations() -> list:
    """Get a list of all migrations made to the SQL table."""
    return query_db("select * from migrations a ")


def get_users() -> list:
    """Get a list of all registered users in the system."""
    return query_db("select * from users a ")


# --- Device Serial Number to Branch Name Mapping ---


def get_device_branch_mappings() -> list:
    """Get all device serial number to branch name mappings."""
    return query_db("SELECT serial_number, branch_name FROM branch_mapping")


def add_device_branch_mapping(serial_number: str, branch_name: str):
    """Add or update a device to branch mapping using an atomic upsert."""
    query = """
    INSERT INTO branch_mapping (serial_number, branch_name)
    VALUES (%s, %s)
    ON DUPLICATE KEY UPDATE branch_name = VALUES(branch_name)
    """
    return query_db(query, (serial_number, branch_name))


def delete_device_branch_mapping(serial_number: str):
    """Delete a device serial number to branch name mapping."""
    return query_db("DELETE FROM branch_mapping WHERE serial_number = %s", (serial_number,))


# --- Employee ID to Designation Mapping ---


def get_employee_designation_mappings() -> list:
    """Get all employee ID to designation mappings."""
    return query_db("SELECT employee_id, designation FROM employee_designation_mapping")


def add_employee_designation_mapping(employee_id: str, designation: str):
    """Add or update an employee designation mapping using an atomic upsert."""
    query = """
    INSERT INTO employee_designation_mapping (employee_id, designation)
    VALUES (%s, %s)
    ON DUPLICATE KEY UPDATE designation = VALUES(designation)
    """
    return query_db(query, (employee_id, designation))


def delete_employee_designation_mapping(employee_id: str):
    """Delete an employee ID to designation mapping."""
    return query_db("DELETE FROM employee_designation_mapping WHERE employee_id = %s", (employee_id,))


# --- Employee ID to Name Mapping ---


def get_employee_name_mappings() -> list:
    """Get all employee ID to name mappings."""
    return query_db("SELECT employee_id, employee_name FROM employee_name_mapping")


def add_employee_name_mapping(employee_id: str, employee_name: str):
    """
    Add or update an employee ID to name mapping using an atomic upsert.
    Relies on the database's UNIQUE constraint on `employee_name` to prevent duplicates.
    """
    query = """
    INSERT INTO employee_name_mapping (employee_id, employee_name)
    VALUES (%s, %s)
    ON DUPLICATE KEY UPDATE employee_name = VALUES(employee_name)
    """
    return query_db(query, (employee_id, employee_name))


def update_employee_name_mapping(employee_id: str, employee_name: str):
    """
    Update an employee name mapping after checking for conflicts.

    This function checks if the new name is already assigned to a different employee.
    If the name is already used by another employee, raises a ValueError.
    If the name is available or already assigned to this employee, updates the mapping.

    Args:
        employee_id: The employee ID to update
        employee_name: The new name to assign

    Raises:
        ValueError: If the name is already assigned to a different employee
    """
    existing = query_db("SELECT employee_id FROM employee_name_mapping WHERE employee_name = %s", (employee_name,))

    if existing and str(existing[0]["employee_id"]) != str(employee_id):
        raise ValueError(f"Employee name '{employee_name}' is already assigned to employee ID {existing[0]['employee_id']}")

    query = """
    INSERT INTO employee_name_mapping (employee_id, employee_name)
    VALUES (%s, %s)
    ON DUPLICATE KEY UPDATE employee_name = VALUES(employee_name)
    """
    return query_db(query, (employee_id, employee_name))


def delete_employee_name_mapping(employee_id: str):
    """Delete an employee ID to name mapping."""
    return query_db("DELETE FROM employee_name_mapping WHERE employee_id = %s", (employee_id,))


# --- Comprehensive Employee Management (Optimized) ---


def get_comprehensive_employee_data(employee_id: str = None) -> list | dict | None:
    """
    Get comprehensive data for one or all employees using an efficient single SQL query.
    This avoids the N+1 query problem and is significantly more performant.
    """
    sql = """
    SELECT
        ids.employee_id,
        COALESCE(nm.employee_name, '') AS employee_name,
        COALESCE(ed.designation, '') AS designation,
        COALESCE(eb.branch_name, '') AS branch_name,
        COALESCE(usm.shift_name, '') AS shift_name,
        usm.shift_start,
        usm.shift_end
    FROM (
        SELECT DISTINCT employee_id FROM (
            SELECT employee_id FROM employee_name_mapping
            UNION
            SELECT employee_id FROM employee_designation_mapping
            UNION
            SELECT employee_id FROM employee_branch_mapping
            UNION
            SELECT user_id AS employee_id FROM user_shift_mapping
        ) AS all_ids
    ) AS ids
    LEFT JOIN employee_name_mapping nm ON ids.employee_id = nm.employee_id
    LEFT JOIN employee_designation_mapping ed ON ids.employee_id = ed.employee_id
    LEFT JOIN employee_branch_mapping eb ON ids.employee_id = eb.employee_id
    LEFT JOIN user_shift_mapping usm ON ids.employee_id = usm.user_id
    """
    params = []
    if employee_id:
        sql += " WHERE ids.employee_id = %s"
        params.append(employee_id)
        result = query_db(sql, tuple(params))
        return result[0] if result else None

    sql += " ORDER BY ids.employee_id"
    return query_db(sql, tuple(params))


def get_employee_ids_for_filters(
    branch_name: str | None = None,
    employee_branch: str | None = None,
    employee_name: str | None = None,
    designation: str | None = None,
) -> set[str]:
    """Get employee IDs that match the given filter criteria."""
    if not any([branch_name, employee_branch, employee_name, designation]):
        return set()

    sql_parts = []
    params = []

    # Base query to get all employee IDs
    base_sql = """
    SELECT DISTINCT ids.employee_id
    FROM (
        SELECT DISTINCT employee_id FROM (
            SELECT employee_id FROM employee_name_mapping
            UNION
            SELECT employee_id FROM employee_designation_mapping
            UNION
            SELECT employee_id FROM employee_branch_mapping
            UNION
            SELECT user_id AS employee_id FROM user_shift_mapping
        ) AS all_ids
    ) AS ids
    """

    joins = []
    wheres = []

    if branch_name:
        # For branch_name filter, we need to find employees who have devices in branches with that name
        # This matches the logic in _build_attendance_filter_query_parts
        joins.append("LEFT JOIN branch_mapping bm ON EXISTS (SELECT 1 FROM attendances a WHERE a.employee_id = ids.employee_id AND a.sn = bm.serial_number)")
        wheres.append("bm.branch_name LIKE %s")
        params.append(f"%{branch_name}%")

    if employee_branch:
        joins.append("LEFT JOIN employee_branch_mapping eb ON eb.employee_id = ids.employee_id")
        wheres.append("eb.branch_name LIKE %s")
        params.append(f"%{employee_branch}%")

    if employee_name:
        joins.append("LEFT JOIN employee_name_mapping en ON en.employee_id = ids.employee_id")
        wheres.append("en.employee_name LIKE %s")
        params.append(f"%{employee_name}%")

    if designation:
        joins.append("LEFT JOIN employee_designation_mapping ed ON ed.employee_id = ids.employee_id")
        wheres.append("ed.designation LIKE %s")
        params.append(f"%{designation}%")

    sql = base_sql
    if joins:
        sql += "\n" + "\n".join(joins)
    if wheres:
        sql += "\nWHERE " + " AND ".join(wheres)

    result = query_db(sql, tuple(params))
    return {str(row["employee_id"]) for row in result if row.get("employee_id")} if result else set()


def add_comprehensive_employee(employee_id: str, employee_name: str = "", designation: str = "", branch_name: str = "", shift_name: str = ""):
    """Add comprehensive employee data. Relies on database constraints for uniqueness."""
    if employee_name:
        add_employee_name_mapping(employee_id, employee_name)
    if designation:
        add_employee_designation_mapping(employee_id, designation)
    if branch_name:
        add_employee_branch_mapping(employee_id, branch_name)

    # Assign provided shift or fall back to default
    target_shift = shift_name if shift_name else get_default_shift()
    if target_shift:
        try:
            assign_shift_template_to_user(employee_id, target_shift)
        except ValueError as e:
            print(f"Warning: Could not assign shift '{target_shift}' to user '{employee_id}': {e}")


def delete_comprehensive_employee(employee_id: str):
    """Delete all mappings for a specific employee."""
    delete_employee_name_mapping(employee_id)
    delete_employee_designation_mapping(employee_id)
    delete_employee_branch_mapping(employee_id)
    delete_user_shift_mapping(employee_id)


def update_comprehensive_employee(employee_id: str, employee_name: str | None = None, designation: str | None = None, branch_name: str | None = None, shift_name: str | None = None):
    """Update specific fields for an employee."""
    delete_comprehensive_employee(employee_id=employee_id)
    if employee_name is not None and employee_name.strip():
        update_employee_name_mapping(employee_id, employee_name)
    if designation is not None and designation.strip():
        add_employee_designation_mapping(employee_id, designation)
    if branch_name is not None and branch_name.strip():
        add_employee_branch_mapping(employee_id, branch_name)
    if shift_name is not None and shift_name.strip():
        assign_shift_template_to_user(employee_id, shift_name)
