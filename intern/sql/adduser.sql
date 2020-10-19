CREATE ROLE :username WITH
  LOGIN
  NOSUPERUSER
  INHERIT
  NOCREATEDB
  NOCREATEROLE
  NOREPLICATION
  PASSWORD :passwd ;
  
  GRANT USAGE ON SCHEMA public TO bi;
  GRANT SELECT ON TABLE public.lif_0 TO :username;
  GRANT SELECT ON TABLE archiv.bestell_kopf TO :username;
  GRANT SELECT ON TABLE archiv.bestell_pos TO :username;
  