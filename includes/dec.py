def log_time(func):
    def wrapper(*args, **kwargs):
        import time
        start = time.time()
        result = func(*args, **kwargs)  # Call the original function
        end = time.time()
        print(f"{func.__name__} took {end - start:.2f} seconds")
        return result
    return wrapper

@log_time
def compute(n):
    return sum(i * i for i in range(n))

compute(1000000)
