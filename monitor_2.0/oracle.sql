SET SERVEROUTPUT ON;
BEGIN
  sp_db_memory_usage;
  END;
  /

CREATE OR REPLACE PROCEDURE sp_db_memory_usage AS
    v_total_mb NUMBER;
    v_used_mb NUMBER;
    v_usage_pct NUMBER;
BEGIN
    -- Total SGA memory allocated (sum of max_size)
    SELECT SUM(max_size)/1024/1024
    INTO v_total_mb
    FROM V$SGA_DYNAMIC_COMPONENTS;

    -- Currently used memory (sum of current_size)
    SELECT SUM(current_size)/1024/1024
    INTO v_used_mb
    FROM V$SGA_DYNAMIC_COMPONENTS;

    -- Usage percentage
    v_usage_pct := ROUND((v_used_mb / v_total_mb) * 100, 2);

    DBMS_OUTPUT.PUT_LINE('Database Memory Usage: ' || v_usage_pct || '%');
EXCEPTION
    WHEN OTHERS THEN
        DBMS_OUTPUT.PUT_LINE('Error calculating memory usage: ' || SQLERRM);
END sp_db_memory_usage;
/


CREATE OR REPLACE FUNCTION fn_db_effective_memory_usage RETURN NUMBER AS
    v_physical_reads    NUMBER;
    v_consistent_gets   NUMBER;
    v_db_block_gets     NUMBER;
    v_logical_gets      NUMBER;
    v_hit_ratio         NUMBER;
BEGIN
    -- Get key statistics from the system
    SELECT VALUE INTO v_physical_reads
    FROM V$SYSSTAT
    WHERE name = 'physical reads';

    SELECT VALUE INTO v_consistent_gets
    FROM V$SYSSTAT
    WHERE name = 'consistent gets';

    SELECT VALUE INTO v_db_block_gets
    FROM V$SYSSTAT
    WHERE name = 'db block gets';

    -- Calculate total logical reads (requests for data)
    v_logical_gets := v_consistent_gets + v_db_block_gets;

    -- Avoid division by zero and calculate hit ratio
    IF v_logical_gets = 0 THEN
        v_hit_ratio := 100; -- If no activity, consider it 100% effective
    ELSE
        v_hit_ratio := ROUND( (1 - (v_physical_reads / v_logical_gets)) * 100, 2 );
    END IF;

    -- A ratio over 100 can happen due to timing, cap it at 100
    v_hit_ratio := LEAST(v_hit_ratio, 100);

    RETURN v_hit_ratio;

EXCEPTION
    WHEN OTHERS THEN
        RETURN NULL;
END fn_db_effective_memory_usage;
/


SET SERVEROUTPUT ON;
BEGIN
sp_db_memory_usage;
END;
/
SET SERVEROUTPUT ON;
BEGIN
fn_db_buffer_cache_usage_pct;
END;
/